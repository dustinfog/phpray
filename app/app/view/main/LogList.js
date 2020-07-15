Ext.define('PhpRay.view.main.LogList', {
    extend: 'Ext.grid.Panel',
    xtype: 'logList',

    requires: [
        'PhpRay.store.LogList'
    ],

    store: {
        type: 'logList'
    },

    columns: [
        { text: '记录者',  dataIndex: 'recorder' },
        { text: '消息', dataIndex: 'message', flex: 1 }
    ],

});