document.addEventListener('DOMContentLoaded', function() {

    var editor =  wp.CodeMirror.fromTextArea(document.getElementById('NHider-custom-css'), {
        lineNumbers: true,
        styleActiveLine: true,
        matchBrackets: true,
        mode: 'css',
        autoCloseBrackets: true,
        theme: 'default'
    });

     editor.on("inputRead", function(cm, change) {
        if (!cm.state.completionActive && !change.text[0].match(/[\s]/)) {
            wp.CodeMirror.commands.autocomplete(cm, null, {completeSingle: false});
        }
    });
});
