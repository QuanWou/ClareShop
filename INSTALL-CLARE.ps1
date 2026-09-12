param(
    [string]$ProjectPath = "D:\Clare"
)

$ErrorActionPreference = "Stop"
$Here = Split-Path -Parent $MyInvocation.MyCommand.Path

if (-not (Test-Path $ProjectPath)) {
    throw "Project not found: $ProjectPath"
}

Write-Host "Installing cinematic Codex skill into $ProjectPath ..."

$skillSource = Join-Path $Here ".codex\skills\cinematic-web-experiences"
$skillTarget = Join-Path $ProjectPath ".codex\skills\cinematic-web-experiences"
$sharedSource = Join-Path $Here "shared\cinematic-web-experiences"
$sharedTarget = Join-Path $ProjectPath "shared\cinematic-web-experiences"
$briefSource = Join-Path $Here "docs\codex\clare-cinematic-motion-brief.md"
$briefTarget = Join-Path $ProjectPath "docs\codex\clare-cinematic-motion-brief.md"

New-Item -ItemType Directory -Force -Path $skillTarget | Out-Null
New-Item -ItemType Directory -Force -Path $sharedTarget | Out-Null
New-Item -ItemType Directory -Force -Path (Split-Path $briefTarget -Parent) | Out-Null

Copy-Item -Path (Join-Path $skillSource "*") -Destination $skillTarget -Recurse -Force
Copy-Item -Path (Join-Path $sharedSource "*") -Destination $sharedTarget -Recurse -Force
Copy-Item -Path $briefSource -Destination $briefTarget -Force

$agentsPath = Join-Path $ProjectPath "AGENTS.md"
$snippetPath = Join-Path $Here "AGENTS.clare-cinematic-snippet.md"
$snippet = Get-Content $snippetPath -Raw -Encoding UTF8

if (Test-Path $agentsPath) {
    $existing = Get-Content $agentsPath -Raw -Encoding UTF8
    if ($existing -notmatch 'CLARE_CINEMATIC_SKILL_START') {
        Add-Content -Path $agentsPath -Value "`r`n$snippet" -Encoding UTF8
        Write-Host "Appended Clare cinematic guidance to existing AGENTS.md"
    } else {
        Write-Host "AGENTS.md already contains Clare cinematic guidance; no duplicate added."
    }
} else {
    Set-Content -Path $agentsPath -Value "# Agent Guidance`r`n`r`n$snippet" -Encoding UTF8
    Write-Host "Created AGENTS.md with Clare cinematic guidance."
}

Write-Host ""
Write-Host "Installed files:"
Write-Host "  .codex\skills\cinematic-web-experiences\"
Write-Host "  shared\cinematic-web-experiences\"
Write-Host "  docs\codex\clare-cinematic-motion-brief.md"
Write-Host "  AGENTS.md (merged, not overwritten)"
Write-Host ""
Write-Host "Done. Reopen Codex in D:\Clare if it was already running so project instructions are reloaded."
