Ext.define('PhpRay.view.main.ClearCache', {
    extend: 'Ext.window.Window',
    height: 200,
    width: 300,
    id: 'ClearCache',
    modal: true, //背景变灰
    closable: true,
    resizable: true,
    bodyStyle: 'background-color: #141414',
    layout: {
        type: 'vbox',
        align: 'stretch' //拉伸使其充满整个父容器
    },
    title: '清缓存',
    items: [{
        xtype: 'combobox',
        displayField: 'Chinese',
        id: 'clearContent',
        height: 30,
        width:  200,
        editable: false,
        fieldStyle:'background-color: #303030; color:black; font-weight: bolder; border: 0.1px solid #cccc, margin: 0 0 0 0; padding: 0',
        store: Ext.create('Ext.data.Store', {
            fields: ['value', 'Chinese'],
            data: [{'value': 'clearInitCode', 'Chinese': '仅当前初始化代码'},
                {'value': 'clearTestCode', 'Chinese': '仅当前测试代码'},
                {'value': 'clearTestAndInitCode', 'Chinese': '当前初始代码和测试代码'},
                {'value': 'clearAllCode', 'Chinese': '所有代码'}]
        }),
    }, {
        xtype: 'button',
        height: 30,
        width:  50,
        margin: '50 100',
        text: 'submit',
        bodyStyle: 'background-color: #ccc; color: white',
        listeners: {
            click: function () {
                let value = Ext.getCmp('clearContent').getSelection().data['value'];
                clearSelectCode(value);
            }
        }
    }]
});
