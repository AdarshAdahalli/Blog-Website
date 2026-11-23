// Simple Text Editor Helper Toolbar
function applyFormat(tag) {
    const textarea = document.getElementById('content');
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    let replacement = '';

    switch(tag) {
        case 'bold':
            replacement = '<b>' + (selectedText || 'Bold Text') + '</b>';
            break;
        case 'italic':
            replacement = '<i>' + (selectedText || 'Italic Text') + '</i>';
            break;
        case 'heading':
            replacement = '<h3>' + (selectedText || 'Heading Title') + '</h3>';
            break;
        case 'ul':
            replacement = '<ul>\n  <li>' + (selectedText || 'Item 1') + '</li>\n  <li>Item 2</li>\n</ul>';
            break;
        case 'link':
            const url = prompt('Enter URL:', 'https://');
            if (url) {
                replacement = '<a href="' + url + '" target="_blank">' + (selectedText || 'Link Text') + '</a>';
            } else {
                return;
            }
            break;
    }

    textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
    textarea.focus();
}
