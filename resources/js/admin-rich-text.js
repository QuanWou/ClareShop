import tinymce from 'tinymce';
import 'tinymce/icons/default';
import 'tinymce/themes/silver';
import 'tinymce/models/dom';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/code';
import 'tinymce/plugins/image';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/media';
import 'tinymce/plugins/table';
import 'tinymce/plugins/wordcount';
import 'tinymce/skins/ui/oxide/skin.css';
import 'tinymce/skins/ui/oxide/content.css';
import 'tinymce/skins/content/default/content.css';

const editors = document.querySelectorAll('[data-rich-text-editor]');

if (editors.length) {
    tinymce.init({
        selector: '[data-rich-text-editor]',
        license_key: 'gpl',
        skin: false,
        content_css: false,
        menubar: 'edit view insert format tools table',
        plugins: 'advlist autolink code image link lists media table wordcount',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link image media table | blockquote | removeformat code',
        height: 620,
        resize: true,
        promotion: false,
        branding: false,
        content_style: 'body { font-family: Arial, sans-serif; font-size: 16px; line-height: 1.75; color: #302821; padding: 20px; } img { max-width: 100%; height: auto; }',
        setup(editor) {
            editor.on('change input undo redo', () => editor.save());
        },
    });
}
