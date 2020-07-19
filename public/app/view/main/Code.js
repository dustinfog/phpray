Ext.define('phpray.view.main.Code', {
    extend: 'Ext.window.Window',
    height: 760,
    width: 1024,
    id: 'editorWindow',
    modal: true, //背景变灰
    closable: true,
    maximizable: true,
    resizable: true,
    layout: {
        type: 'vbox',
        align: 'stretch' //拉伸使其充满整个父容器
    },
    items: [{
        xtype: 'panel',
        height: 728,
        width: '100%',
        items: [{
            xtype: 'panel',
            id: 'tool',
            height: 30,
            width: '100%',
            items: [{
                xtype: 'button',
                id: 'save',
                cls: 'saveButton',
                iconCls: 'save',
                listeners:{
                    click:function () {
                        save();
                    }
                }
            }, {
                xtype: 'button',
                cls: 'reverseButton',
                id: 'reverse',
                iconCls:'reverse',
                listeners: {
                    click:function () {
                        Ext.Ajax.request({
                                url: 'index.php',
                                method: 'POST',
                                params: {project: project, fileName: fileName, action: 'main.reverse'},
                                dataType: 'json',
                                success: function (code, options) {
                                    let response = Ext.decode(code.responseText);
                                    if (response) {
                                        if (response.error) {
                                            alert(response.error);
                                            return;
                                        }
                                        Ext.getCmp('reverse').disable();
                                    }
                                }
                            }
                        );
                    }
                }
            }]
        }, {
            xtype: "textarea",
            id: 'editor',
            height: 690,
            width: '100%',
        }]
    }]

});
