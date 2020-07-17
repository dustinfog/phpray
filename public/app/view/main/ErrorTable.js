Ext.define('phpray.view.main.ErrorTable', {
    extend: 'Ext.grid.Panel',
    xtype: 'errorTable',

    requires: [
        'phpray.store.ErrorTable'
    ],

    store: {
        type: 'errorTable'
    },

    columns: [
        { text: '调用',  dataIndex: 'call', width: '23%'},
        { text: '文件', dataIndex: 'file', width: '70%'},
        { text: '行', dataIndex: 'line', width: '6%' }
    ],
});
