Ext.define('PhpRay.view.main.ErrorList', {
    extend: 'Ext.grid.Panel',
    xtype: 'errorList',

    requires: [
        'PhpRay.store.ErrorList'
    ],

    store: {
        type: 'errorList'
    },

    columns: [
        { text: '类型',  dataIndex: 'type', width: '14%'},
        { text: '消息', dataIndex: 'message', width: '40%'},
        { text: '文件', dataIndex: 'file', width: '40%'},
        { text: '行', dataIndex: 'line', width: '5%' }
    ],
});
