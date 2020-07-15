/**
 * The main application class. An instance of this class is created by app.js when it
 * calls Ext.application(). This is the ideal place to handle application launch and
 * initialization details.
 */
Ext.define('PhpRay.Application', {
    extend: 'Ext.app.Application',

    name: 'PhpRay',

    quickTips: false,
    platformConfig: {
        desktop: {
            quickTips: true
        }
    },

    launch: function () { //页面加载完成后自动调用launch
        codeEditorInit('<?php\r');
        codeEditorTest('<?php\r');
    }
});
