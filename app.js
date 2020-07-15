/*
 * This file launches the application by asking Ext JS to create
 * and launch() the Application class.
 */
Ext.application({
    extend: 'PhpRay.Application',

    name: 'PhpRay',

    requires: [
        // This will automatically load all classes in the PhpRay namespace
        // so that application classes do not need to require each other.
        'PhpRay.*'
    ],

    // The name of the initial view to create.
    mainView: 'PhpRay.view.main.Main',
});
