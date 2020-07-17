/*
 * This file launches the application by asking Ext JS to create
 * and launch() the Application class.
 */
Ext.application({
    extend: 'phpray.Application',

    name: 'phpray',

    requires: [
        // This will automatically load all classes in the phpray namespace
        // so that application classes do not need to require each other.
        'phpray.*'
    ],

    // The name of the initial view to create.
    mainView: 'phpray.view.main.Main'
});
