[CmdletBinding()]
param(
    [ValidateRange(1, 65535)]
    [int] $Port = 8000,

    [switch] $NoStartLaravel,

    [switch] $Watch
)

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

# ============================================================
# PATHS
# ============================================================

$projectRoot = (Resolve-Path -LiteralPath $PSScriptRoot).Path

$environmentPath = Join-Path $projectRoot '.env'

$runtimeDirectory = Join-Path $projectRoot 'storage\app\dev-tunnel'

$statePath = Join-Path $runtimeDirectory 'state.json'

$cloudflaredOutputPath = Join-Path $runtimeDirectory 'cloudflared.out.log'
$cloudflaredErrorPath  = Join-Path $runtimeDirectory 'cloudflared.err.log'

$laravelOutputPath = Join-Path $runtimeDirectory 'laravel.out.log'
$laravelErrorPath  = Join-Path $runtimeDirectory 'laravel.err.log'

# Local Laravel URL
$originUrl = "http://localhost:$Port"


# ============================================================
# FIND CLOUDFLARED
# ============================================================

function Resolve-CloudflaredPath {

    $installedCommand = Get-Command cloudflared.exe -ErrorAction SilentlyContinue |
        Select-Object -First 1

    $localAppData = [Environment]::GetFolderPath('LocalApplicationData')

    $candidatePaths = @(
        $(if ($installedCommand) {
            $installedCommand.Source
        }),

        'C:\Program Files (x86)\cloudflared\cloudflared.exe',

        'C:\Program Files\cloudflared\cloudflared.exe',

        (Join-Path $runtimeDirectory 'cloudflared.exe'),

        $(if ($localAppData) {
            Join-Path $localAppData 'Temp\cloudflared-windows-amd64.exe'
        })
    ) | Where-Object { $_ }


    foreach ($candidatePath in $candidatePaths) {

        if (Test-Path -LiteralPath $candidatePath -PathType Leaf) {
            try {
                & $candidatePath --version *> $null

                if ($LASTEXITCODE -eq 0) {
                    return (Resolve-Path -LiteralPath $candidatePath).Path
                }
            }
            catch {
                # Tiếp tục tìm hoặc tải lại nếu file cục bộ bị gián đoạn khi tải.
            }
        }
    }

    $localCloudflaredPath = Join-Path $runtimeDirectory 'cloudflared.exe'
    $downloadPath = "$localCloudflaredPath.download"
    Write-Host 'Chua co cloudflared. Dang tai ban Windows AMD64 tu Cloudflare...' -ForegroundColor Yellow
    $downloadUrl = 'https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe'
    $curlCommand = Get-Command curl.exe -ErrorAction SilentlyContinue | Select-Object -First 1

    if ($curlCommand) {
        & $curlCommand.Source `
            --location `
            --fail `
            --retry 3 `
            --continue-at - `
            --progress-bar `
            --output $downloadPath `
            $downloadUrl

        if ($LASTEXITCODE -ne 0) {
            throw 'Khong the tai cloudflared.exe bang curl.'
        }
    }
    else {
        Invoke-WebRequest `
            -Uri $downloadUrl `
            -OutFile $downloadPath `
            -UseBasicParsing `
            -TimeoutSec 1800
    }

    Move-Item -LiteralPath $downloadPath -Destination $localCloudflaredPath -Force

    if (-not (Test-Path -LiteralPath $localCloudflaredPath -PathType Leaf)) {
        throw 'Khong the tai cloudflared.exe.'
    }

    return (Resolve-Path -LiteralPath $localCloudflaredPath).Path
}


# ============================================================
# CHECK LARAVEL
# ============================================================

function Test-LaravelOrigin {

    param(
        [string] $Url
    )

    try {

        # Laravel mặc định có route /up
        $response = Invoke-WebRequest `
            -Uri "$Url/up" `
            -UseBasicParsing `
            -TimeoutSec 3

        return (
            $response.StatusCode -ge 200 -and
            $response.StatusCode -lt 500
        )

    }
    catch {

        # Nếu /up không tồn tại thì thử trang /
        try {

            $response = Invoke-WebRequest `
                -Uri $Url `
                -UseBasicParsing `
                -TimeoutSec 3

            return (
                $response.StatusCode -ge 200 -and
                $response.StatusCode -lt 500
            )

        }
        catch {

            return $false
        }
    }
}


function Test-PublicTunnel {

    param([string] $PublicUrl)

    if (-not $PublicUrl) {
        return $false
    }

    try {
        $response = Invoke-WebRequest `
            -Uri "$($PublicUrl.TrimEnd('/'))/up" `
            -UseBasicParsing `
            -TimeoutSec 8

        return $response.StatusCode -ge 200 -and $response.StatusCode -lt 500
    }
    catch {
        return $false
    }
}


# ============================================================
# READ LOG FILE WHILE CLOUDFLARED IS WRITING
# ============================================================

function Read-SharedTextFile {

    param(
        [string] $Path
    )

    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
        return ''
    }

    $fileStream = [IO.FileStream]::new(
        $Path,
        [IO.FileMode]::Open,
        [IO.FileAccess]::Read,
        [IO.FileShare]::ReadWrite
    )

    $streamReader = [IO.StreamReader]::new($fileStream)

    try {

        return $streamReader.ReadToEnd()

    }
    finally {

        $streamReader.Dispose()
        $fileStream.Dispose()
    }
}


# ============================================================
# LOAD SAVED TUNNEL STATE
# ============================================================

function Get-ManagedTunnelState {

    if (-not (Test-Path -LiteralPath $statePath -PathType Leaf)) {
        return $null
    }

    try {

        return Get-Content `
            -LiteralPath $statePath `
            -Raw |
            ConvertFrom-Json

    }
    catch {

        return $null
    }
}


# ============================================================
# CHECK EXISTING TUNNEL
# ============================================================

function Get-RunningManagedTunnel {

    $savedState = Get-ManagedTunnelState

    if (
        -not $savedState -or
        -not $savedState.cloudflared_pid -or
        -not $savedState.url
    ) {

        return $null
    }


    $runningProcess = Get-Process `
        -Id ([int] $savedState.cloudflared_pid) `
        -ErrorAction SilentlyContinue


    if (
        -not $runningProcess -or
        $runningProcess.ProcessName -notlike 'cloudflared*'
    ) {

        return $null
    }


    if (-not (Test-PublicTunnel -PublicUrl $savedState.url)) {
        $tunnelLog = (Read-SharedTextFile -Path $cloudflaredOutputPath) + (Read-SharedTextFile -Path $cloudflaredErrorPath)

        if ($tunnelLog -notmatch 'Registered tunnel connection') {
            Write-Host 'Tunnel da luu khong con ket noi. Se tao tunnel moi.' -ForegroundColor Yellow
            Stop-Process -Id $runningProcess.Id -Force -ErrorAction SilentlyContinue

            return $null
        }
    }

    return $savedState
}


# ============================================================
# UPDATE APP_URL IN .ENV
# ============================================================

function Set-ApplicationUrl {

    param(
        [string] $PublicUrl
    )

    if (-not (Test-Path -LiteralPath $environmentPath -PathType Leaf)) {

        throw "Khong tim thay file .env tai: $environmentPath"
    }


    $environmentContent = [IO.File]::ReadAllText($environmentPath)

    $applicationUrlLine = "APP_URL=$PublicUrl"


    if ($environmentContent -match '(?m)^APP_URL=.*$') {

        $environmentContent = [regex]::Replace(
            $environmentContent,
            '(?m)^APP_URL=.*$',
            $applicationUrlLine
        )

    }
    else {

        $environmentContent =
            $environmentContent.TrimEnd() +
            [Environment]::NewLine +
            $applicationUrlLine +
            [Environment]::NewLine
    }


    [IO.File]::WriteAllText(
        $environmentPath,
        $environmentContent,
        [Text.UTF8Encoding]::new($false)
    )
}


# ============================================================
# CLEAR LARAVEL CACHE
# ============================================================

function Clear-LaravelCache {

    param(
        [string] $PhpPath
    )

    Push-Location $projectRoot

    try {

        & $PhpPath artisan config:clear | Out-Host

        if ($LASTEXITCODE -ne 0) {

            throw 'Laravel khong the xoa config cache.'
        }

    }
    finally {

        Pop-Location
    }
}


# ============================================================
# DISPLAY RESULT
# ============================================================

function Show-TunnelResult {

    param(
        [string] $PublicUrl,
        [bool] $Reused
    )


    $paypalWebhookUrl = "$PublicUrl/webhooks/paypal"
    $payosWebhookUrl = "$PublicUrl/webhooks/payos"
    try {

        Set-Clipboard -Value $paypalWebhookUrl

        $clipboardMessage =
            'PayPal webhook da duoc copy vao clipboard.'

    }
    catch {

        $clipboardMessage =
            'Khong the tu dong copy clipboard.'
    }


    Write-Host ''

    Write-Host '=====================================================' -ForegroundColor DarkGray

    if ($Reused) {

        Write-Host 'DANG SU DUNG LAI CLOUDFLARE TUNNEL' `
            -ForegroundColor Cyan

    }
    else {

        Write-Host 'CLOUDFLARE QUICK TUNNEL DA SAN SANG' `
            -ForegroundColor Green
    }

    Write-Host '=====================================================' -ForegroundColor DarkGray

    Write-Host ''

    Write-Host "Laravel local:   $originUrl" `
        -ForegroundColor White

    Write-Host "Website public:  $PublicUrl" `
        -ForegroundColor Green

    Write-Host ''

    Write-Host "PayPal webhook:  $paypalWebhookUrl" `
        -ForegroundColor Yellow

    Write-Host "payOS webhook:   $payosWebhookUrl" `
        -ForegroundColor Yellow

    Write-Host $clipboardMessage `
        -ForegroundColor Cyan

    Write-Host ''

    Write-Host 'APP_URL trong .env da duoc cap nhat:' `
        -ForegroundColor DarkGray

    Write-Host "APP_URL=$PublicUrl" `
        -ForegroundColor Cyan

    Write-Host ''

    $environmentContent = [IO.File]::ReadAllText($environmentPath)

    if (
        $environmentContent -notmatch '(?m)^PAYPAL_CLIENT_ID=.+$' -or
        $environmentContent -notmatch '(?m)^PAYPAL_CLIENT_SECRET=.+$'
    ) {
        Write-Host 'CANH BAO: PayPal keys van dang comment hoac chua co ten bien trong .env.' `
            -ForegroundColor Yellow
        Write-Host 'Can dat PAYPAL_CLIENT_ID va PAYPAL_CLIENT_SECRET truoc khi thanh toan thu.' `
            -ForegroundColor Yellow
        Write-Host ''
    }

    if (
        $environmentContent -notmatch '(?m)^PAYOS_CLIENT_ID=.+$' -or
        $environmentContent -notmatch '(?m)^PAYOS_API_KEY=.+$' -or
        $environmentContent -notmatch '(?m)^PAYOS_CHECKSUM_KEY=.+$'
    ) {
        Write-Host 'CANH BAO: Ba khoa payOS van dang comment hoac chua co ten bien trong .env.' `
            -ForegroundColor Yellow
        Write-Host 'Can dat PAYOS_CLIENT_ID, PAYOS_API_KEY va PAYOS_CHECKSUM_KEY.' `
            -ForegroundColor Yellow
        Write-Host ''
    }
}


# ============================================================
# CREATE OR UPDATE PAYPAL WEBHOOK
# ============================================================

function Get-EnvironmentValue {

    param([string] $Name)

    $environmentContent = [IO.File]::ReadAllText($environmentPath)
    $match = [regex]::Match($environmentContent, "(?m)^$([regex]::Escape($Name))=(.*)$")

    if (-not $match.Success) {
        return $null
    }

    return $match.Groups[1].Value.Trim().Trim('"').Trim("'")
}


function Set-EnvironmentValue {

    param(
        [string] $Name,
        [string] $Value
    )

    $environmentContent = [IO.File]::ReadAllText($environmentPath)
    $line = "$Name=$Value"
    $pattern = "(?m)^$([regex]::Escape($Name))=.*$"

    if ($environmentContent -match $pattern) {
        $environmentContent = [regex]::Replace($environmentContent, $pattern, $line)
    }
    else {
        $environmentContent = $environmentContent.TrimEnd() + [Environment]::NewLine + $line + [Environment]::NewLine
    }

    [IO.File]::WriteAllText($environmentPath, $environmentContent, [Text.UTF8Encoding]::new($false))
}


function Sync-PayPalWebhook {

    param([string] $PublicUrl)

    $clientId = Get-EnvironmentValue -Name 'PAYPAL_CLIENT_ID'
    $clientSecret = Get-EnvironmentValue -Name 'PAYPAL_CLIENT_SECRET'

    if (-not $clientId -or -not $clientSecret) {
        Write-Host 'Bo qua dong bo webhook: PayPal keys chua duoc cau hinh.' -ForegroundColor Yellow
        return
    }

    try {
        $mode = Get-EnvironmentValue -Name 'PAYPAL_MODE'
        $apiBaseUrl = if ($mode -eq 'live') { 'https://api-m.paypal.com' } else { 'https://api-m.sandbox.paypal.com' }
        $basicBytes = [Text.Encoding]::ASCII.GetBytes("${clientId}:${clientSecret}")
        $basicToken = [Convert]::ToBase64String($basicBytes)
        $oauth = Invoke-RestMethod `
            -Method Post `
            -Uri "$apiBaseUrl/v1/oauth2/token" `
            -Headers @{ Authorization = "Basic $basicToken"; Accept = 'application/json' } `
            -ContentType 'application/x-www-form-urlencoded' `
            -Body 'grant_type=client_credentials' `
            -TimeoutSec 20

        $headers = @{
            Authorization = "Bearer $($oauth.access_token)"
            Accept = 'application/json'
            'Content-Type' = 'application/json'
        }
        $webhookUrl = "$PublicUrl/webhooks/paypal"
        $webhookId = Get-EnvironmentValue -Name 'PAYPAL_WEBHOOK_ID'

        if ($webhookId) {
            try {
                $patchBody = @(@{ op = 'replace'; path = '/url'; value = $webhookUrl }) | ConvertTo-Json
                Invoke-RestMethod `
                    -Method Patch `
                    -Uri "$apiBaseUrl/v1/notifications/webhooks/$webhookId" `
                    -Headers $headers `
                    -Body $patchBody `
                    -TimeoutSec 20 | Out-Null
            }
            catch {
                $webhookId = $null
            }
        }

        if (-not $webhookId) {
            $createBody = @{
                url = $webhookUrl
                event_types = @(
                    @{ name = 'CHECKOUT.ORDER.APPROVED' }
                    @{ name = 'PAYMENT.CAPTURE.COMPLETED' }
                    @{ name = 'PAYMENT.CAPTURE.DENIED' }
                    @{ name = 'PAYMENT.CAPTURE.REFUNDED' }
                )
            } | ConvertTo-Json -Depth 5
            $createdWebhook = Invoke-RestMethod `
                -Method Post `
                -Uri "$apiBaseUrl/v1/notifications/webhooks" `
                -Headers $headers `
                -Body $createBody `
                -TimeoutSec 20
            $webhookId = $createdWebhook.id
            Set-EnvironmentValue -Name 'PAYPAL_WEBHOOK_ID' -Value $webhookId
        }

        Write-Host "PayPal webhook da dong bo: $webhookUrl" -ForegroundColor Green
    }
    catch {
        Write-Host "Khong the tu dong dong bo PayPal webhook: $($_.Exception.Message)" -ForegroundColor Yellow
    }
}


function Sync-PayOsWebhook {

    param([string] $PublicUrl)

    $clientId = Get-EnvironmentValue -Name 'PAYOS_CLIENT_ID'
    $apiKey = Get-EnvironmentValue -Name 'PAYOS_API_KEY'

    if (-not $clientId -or -not $apiKey) {
        Write-Host 'Bo qua dong bo webhook: payOS keys chua duoc cau hinh.' -ForegroundColor Yellow
        return $false
    }

    $apiBaseUrl = Get-EnvironmentValue -Name 'PAYOS_BASE_URL'
    if (-not $apiBaseUrl) {
        $apiBaseUrl = 'https://api-merchant.payos.vn'
    }

    $webhookUrl = "$PublicUrl/webhooks/payos"
    $body = @{ webhookUrl = $webhookUrl } | ConvertTo-Json
    $curlCommand = Get-Command curl.exe -ErrorAction SilentlyContinue | Select-Object -First 1

    if (-not $curlCommand) {
        Write-Host 'Khong tim thay curl.exe de dong bo payOS theo IPv4/IPv6.' -ForegroundColor Red
        return $false
    }

    for ($attempt = 1; $attempt -le 4; $attempt++) {
        foreach ($ipFamily in @('6', '4')) {
            $curlArguments = @(
                "-$ipFamily",
                '--silent',
                '--show-error',
                '--fail',
                '--connect-timeout', '8',
                '--max-time', '25',
                '--request', 'POST',
                '--header', "x-client-id: $clientId",
                '--header', "x-api-key: $apiKey",
                '--header', 'Accept: application/json',
                '--header', 'Content-Type: application/json',
                '--data', $body,
                '--output', 'NUL',
                "$($apiBaseUrl.TrimEnd('/'))/confirm-webhook"
            )

            & $curlCommand.Source $curlArguments

            if ($LASTEXITCODE -eq 0) {
                # Persist only an endpoint and IP family that payOS accepted.
                Set-EnvironmentValue -Name 'PAYOS_WEBHOOK_URL' -Value $webhookUrl
                Set-EnvironmentValue -Name 'PAYOS_IP_RESOLVE' -Value $ipFamily

                Write-Host "payOS webhook da dong bo qua IPv${ipFamily}: $webhookUrl" -ForegroundColor Green
                return $true
            }

            Write-Host "Dong bo payOS lan $attempt/4 qua IPv${ipFamily} that bai." -ForegroundColor Yellow
        }

        if ($attempt -lt 4) {
            Start-Sleep -Seconds ([Math]::Min($attempt * 2, 6))
        }
    }

    Write-Host 'CANH BAO: payOS webhook chua duoc dong bo. Khong nen thanh toan thu cho den khi khoi dong lai thanh cong.' -ForegroundColor Red
    return $false
}


# ============================================================
# CREATE RUNTIME DIRECTORY
# ============================================================

New-Item `
    -ItemType Directory `
    -Path $runtimeDirectory `
    -Force |
    Out-Null


# ============================================================
# FIND PHP
# ============================================================

$phpPath = $env:CLARE_PHP

if (-not $phpPath -or -not (Test-Path -LiteralPath $phpPath -PathType Leaf)) {
    $phpCommand = Get-Command php.exe -ErrorAction SilentlyContinue | Select-Object -First 1
    $phpPath = if ($phpCommand) { $phpCommand.Source } else { $null }
}


if (-not $phpPath) {

    throw @"
Khong tim thay php.exe trong PATH.

Thu:

    php -v
"@
}


# ============================================================
# REUSE EXISTING CLOUDFLARE TUNNEL
# ============================================================

$managedTunnel = Get-RunningManagedTunnel


if ($managedTunnel) {

    Write-Host ''

    Write-Host 'Tim thay Cloudflare Tunnel dang chay...' `
        -ForegroundColor Cyan


    Set-ApplicationUrl `
        -PublicUrl $managedTunnel.url

    Sync-PayPalWebhook `
        -PublicUrl $managedTunnel.url

    Sync-PayOsWebhook `
        -PublicUrl $managedTunnel.url


    Clear-LaravelCache `
        -PhpPath $phpPath


    Show-TunnelResult `
        -PublicUrl $managedTunnel.url `
        -Reused $true

    if ($Watch) {
        Write-Host 'Dang giam sat Cloudflare tunnel. Nhan Ctrl+C de dung.' -ForegroundColor DarkGray
        Wait-Process -Id ([int] $managedTunnel.cloudflared_pid)
        throw 'Cloudflare tunnel da dung. Clare cung dung de tranh tiep tuc nhan thanh toan khi webhook mat ket noi.'
    }


    exit 0
}


# ============================================================
# START LARAVEL IF NOT RUNNING
# ============================================================

$laravelProcess = $null


if (-not (Test-LaravelOrigin -Url $originUrl)) {

    if ($NoStartLaravel) {
        Write-Host "Dang cho Laravel san sang tai $originUrl ..." -ForegroundColor Cyan
        $originDeadline = [DateTime]::UtcNow.AddSeconds(30)

        while ([DateTime]::UtcNow -lt $originDeadline -and -not (Test-LaravelOrigin -Url $originUrl)) {
            Start-Sleep -Milliseconds 500
        }

        if (-not (Test-LaravelOrigin -Url $originUrl)) {
            throw "Laravel chua san sang tai $originUrl sau 30 giay."
        }
    }


    Write-Host ''

    Write-Host "Laravel chua chay tai $originUrl" `
        -ForegroundColor Yellow

    Write-Host 'Dang khoi dong Laravel...' `
        -ForegroundColor Cyan


    [IO.File]::WriteAllText(
        $laravelOutputPath,
        ''
    )

    [IO.File]::WriteAllText(
        $laravelErrorPath,
        ''
    )


    # Dùng artisan serve thay vì PHP built-in server thủ công
    $laravelArguments = @(
        'artisan',
        'serve',
        '--host=localhost',
        "--port=$Port"
    )


    $laravelProcess = Start-Process `
        -FilePath $phpPath `
        -ArgumentList $laravelArguments `
        -WorkingDirectory $projectRoot `
        -RedirectStandardOutput $laravelOutputPath `
        -RedirectStandardError $laravelErrorPath `
        -WindowStyle Hidden `
        -PassThru


    # Chờ Laravel tối đa 20 giây
    $laravelDeadline = [DateTime]::UtcNow.AddSeconds(20)


    while (
        [DateTime]::UtcNow -lt $laravelDeadline -and
        -not (Test-LaravelOrigin -Url $originUrl)
    ) {

        Start-Sleep -Milliseconds 500
    }


    if (-not (Test-LaravelOrigin -Url $originUrl)) {

        Stop-Process `
            -Id $laravelProcess.Id `
            -Force `
            -ErrorAction SilentlyContinue


        throw @"
Khong the khoi dong Laravel.

Hay xem log:

$laravelErrorPath
"@
    }


    Write-Host "Laravel da chay tai $originUrl" `
        -ForegroundColor Green
}
else {

    Write-Host ''

    Write-Host "Laravel dang chay tai $originUrl" `
        -ForegroundColor Green
}


# ============================================================
# FIND CLOUDFLARED
# ============================================================

$cloudflaredPath = Resolve-CloudflaredPath


Write-Host ''

Write-Host 'Cloudflared:' `
    -ForegroundColor DarkGray

Write-Host $cloudflaredPath `
    -ForegroundColor Cyan


# ============================================================
# CLEAR OLD LOGS
# ============================================================

[IO.File]::WriteAllText(
    $cloudflaredOutputPath,
    ''
)

[IO.File]::WriteAllText(
    $cloudflaredErrorPath,
    ''
)


# ============================================================
# START CLOUDFLARE QUICK TUNNEL
# ============================================================

Write-Host ''

Write-Host "Dang tao tunnel cho $originUrl ..." `
    -ForegroundColor Cyan


$cloudflaredArguments = @(
    'tunnel',
    '--protocol',
    'http2',
    '--edge-ip-version',
    '4',
    '--url',
    $originUrl
)


$cloudflaredProcess = Start-Process `
    -FilePath $cloudflaredPath `
    -ArgumentList $cloudflaredArguments `
    -WorkingDirectory $projectRoot `
    -RedirectStandardOutput $cloudflaredOutputPath `
    -RedirectStandardError $cloudflaredErrorPath `
    -WindowStyle Hidden `
    -PassThru


# ============================================================
# GET TRYCLOUDFLARE URL
# ============================================================

$publicUrl = $null

$tunnelDeadline = [DateTime]::UtcNow.AddSeconds(45)

$urlPattern = 'https://[a-z0-9-]+\.trycloudflare\.com'


while ([DateTime]::UtcNow -lt $tunnelDeadline) {

    if ($cloudflaredProcess.HasExited) {
        break
    }


    $tunnelLog = ''

    $tunnelLog += Read-SharedTextFile `
        -Path $cloudflaredOutputPath

    $tunnelLog += Read-SharedTextFile `
        -Path $cloudflaredErrorPath


    $urlMatch = [regex]::Match(
        $tunnelLog,
        $urlPattern
    )


    if ($urlMatch.Success) {

        $publicUrl = $urlMatch.Value

        break
    }


    Start-Sleep -Milliseconds 500
}


# ============================================================
# CLOUDFLARE FAILED
# ============================================================

if (-not $publicUrl) {

    Stop-Process `
        -Id $cloudflaredProcess.Id `
        -Force `
        -ErrorAction SilentlyContinue


    if ($laravelProcess) {

        Stop-Process `
            -Id $laravelProcess.Id `
            -Force `
            -ErrorAction SilentlyContinue
    }


    $recentError = if (
        Test-Path -LiteralPath $cloudflaredErrorPath
    ) {

        (
            Get-Content `
                -LiteralPath $cloudflaredErrorPath `
                -Tail 20
        ) -join [Environment]::NewLine

    }
    else {

        'Khong co log cloudflared.'
    }


    throw @"
Khong lay duoc URL Quick Tunnel sau 45 giay.

Cloudflare log:

$recentError
"@
}


# A quick-tunnel URL is printed before the connector is necessarily ready.
# Do not publish callback URLs or contact payment providers until Cloudflare
# has registered an actual edge connection.
$connectionDeadline = [DateTime]::UtcNow.AddSeconds(60)
$tunnelConnected = $false

while ([DateTime]::UtcNow -lt $connectionDeadline) {
    $cloudflaredProcess.Refresh()
    if ($cloudflaredProcess.HasExited) {
        break
    }

    $connectionLog = (Read-SharedTextFile -Path $cloudflaredOutputPath) + (Read-SharedTextFile -Path $cloudflaredErrorPath)
    if ($connectionLog -match 'Registered tunnel connection') {
        $tunnelConnected = $true
        break
    }

    Start-Sleep -Milliseconds 500
}

if (-not $tunnelConnected) {
    Stop-Process -Id $cloudflaredProcess.Id -Force -ErrorAction SilentlyContinue

    if ($laravelProcess) {
        Stop-Process -Id $laravelProcess.Id -Force -ErrorAction SilentlyContinue
    }

    $recentError = (Get-Content -LiteralPath $cloudflaredErrorPath -Tail 25 -ErrorAction SilentlyContinue) -join [Environment]::NewLine
    throw @"
Cloudflare da cap URL nhung tunnel khong ket noi duoc trong 60 giay.
Khong cap nhat APP_URL/webhook de tranh payOS goi vao dia chi hong.

Cloudflare log:

$recentError
"@
}


# ============================================================
# SAVE STATE
# ============================================================

$runtimeState = [ordered]@{

    url = $publicUrl

    paypal_webhook_url = "$publicUrl/webhooks/paypal"

    payos_webhook_url = "$publicUrl/webhooks/payos"

    cloudflared_pid = $cloudflaredProcess.Id

    laravel_pid = if ($laravelProcess) {
        $laravelProcess.Id
    }
    else {
        $null
    }

    origin = $originUrl

    started_at = [DateTime]::UtcNow.ToString('o')
}


[IO.File]::WriteAllText(
    $statePath,
    ($runtimeState | ConvertTo-Json),
    [Text.UTF8Encoding]::new($false)
)


# ============================================================
# UPDATE LARAVEL APP_URL
# ============================================================

Set-ApplicationUrl `
    -PublicUrl $publicUrl

Sync-PayPalWebhook `
    -PublicUrl $publicUrl

Sync-PayOsWebhook `
    -PublicUrl $publicUrl


Clear-LaravelCache `
    -PhpPath $phpPath


# ============================================================
# FINISHED
# ============================================================

Show-TunnelResult `
    -PublicUrl $publicUrl `
    -Reused $false

if ($Watch) {
    Write-Host 'Dang giam sat Cloudflare tunnel. Nhan Ctrl+C de dung.' -ForegroundColor DarkGray
    Wait-Process -Id $cloudflaredProcess.Id
    throw 'Cloudflare tunnel da dung. Clare cung dung de tranh tiep tuc nhan thanh toan khi webhook mat ket noi.'
}
