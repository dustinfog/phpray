Ext.define('phpray.view.main.LogList', {
    extend: 'Ext.grid.Panel',
    xtype: 'logList',

    requires: [
        'phpray.store.LogList'
    ],

    store: {
        type: 'logList'
    },

    columns: [
        { text: '记录者',  dataIndex: 'recorder' },
        { text: '消息', dataIndex: 'visibleMessage', flex: 1 },
    ],

});