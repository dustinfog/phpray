Ext.define('phpray.view.main.MemoWindow', {
    extend: 'Ext.window.Window',
    height: 500,
    width: 500,
    title: '备忘录',
    id: 'memo',
    modal: true, //背景变灰
    closable: true,
    resizable: true,
    bodyStyle: 'background-color: #303030; color: white',
    layout: {
        type: 'vbox',
        align: 'stretch' //拉伸使其充满整个父容器
    },
    items: [{
        xtype: 'textarea',
        height: '90%',
        width: '100%',
        id: 'memoContent',
        bodyStyle: 'background-color:#141414; color: white',
    },{
        xtype: 'button',
        id: 'memoButton',
        height: '8%',
        text: 'submit',
        bodyStyle: 'background-color: #3C3F41; color: white',
        listeners: {
            click: function () {
                let content = Ext.getCmp('memoContent').getValue();
                let request = indexedDB.open('phpRay');
                request.onsuccess = function (event) {
                    let db = event.target.result;
                    let store = db.transaction('Memo', 'readwrite').objectStore('Memo');
                    let reqPut = store.put({'value': 'memo', 'memoContent': content});
                    reqPut.onsuccess = function (event) {
                        Ext.Msg.alert('', '备忘录保存成功');
                    };
                    db.close();
                };

            }
        }
    }]
});
