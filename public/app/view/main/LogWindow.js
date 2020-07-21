Ext.define('phpray.view.main.LogWindow', { //日志弹窗
    extend: 'Ext.window.Window',
    height: 758,
    width: 1024,
    layout: {
        type: 'vbox',
        align: 'stretch' //拉伸使其充满整个父容器
    },
    title: '日志详情',
    modal: true, //背景变灰
    id: 'logWindow',
    closable: true,
    items: [{
        xtype: 'panel',
        width: '100%',
        height: 30,
        items: [{
            xtype: 'panel',
            items: [{
                xtype: 'button',
                width: 100,
                cls: 'btn',
                margin: '0 20 0 300',
                text: '上一页',
                listeners: {
                    click: function () {
                        if (logPage <= 0) {
                            return;
                        }
                        logPage--;
                        Ext.getCmp('logPage').setText((logPage + 1) + '/' + logTotalPage); //页数
                        Ext.getCmp('titleLog').setHtml('调用者:  ' + resultLogs[logPage].logger);
                        let message = resultLogs[logPage].message;
                        Ext.getCmp('logTree').store.getNodeById('treeLog').removeAll(true);
                        if (message instanceof Object) {
                            let logData = returnRootData(message);
                            Ext.getCmp('logTree').store.getNodeById('treeLog').appendChild(logData);
                            Ext.getCmp('logTree').expandAll();
                            Ext.getCmp('logTree').show();
                            Ext.getCmp('logMessage').hide();
                        } else {
                            Ext.getCmp('logMessage').setHtml(message);
                            Ext.getCmp('logTree').hide();
                            Ext.getCmp('logMessage').show();

                        }
                        Ext.getCmp('logTable').store.removeAll();
                        Ext.getCmp('logTable').store.add(resultLogs[logPage].backtrace);
                        document.getElementById('logContent').innerHTML = '';
                    }
                }
            }, {
                xtype: 'button',
                id: 'logPage',
                width: 100,
                height: 30,
                disabled: true,
                bodyStyle: 'background-color: white, color: black',
            }, {
                xtype: 'button',
                cls: 'btn',
                width: 100,
                margin: '0 20',
                text: '下一页',
                listeners: {
                    click: function () {
                        if (logPage >= logTotalPage - 1) {
                            return;
                        }
                        logPage++;
                        Ext.getCmp('logPage').setText((logPage + 1) + '/' + logTotalPage); //页数
                        Ext.getCmp('titleLog').setHtml('调用者:  ' + resultLogs[logPage].logger);
                        let message = resultLogs[logPage].message;
                        if (message instanceof Object) {
                            let logData = returnRootData(message);
                            Ext.getCmp('logTree').store.getNodeById('treeLog').removeAll(true);
                            Ext.getCmp('logTree').store.getNodeById('treeLog').appendChild(logData);
                            Ext.getCmp('logTree').expandAll();
                            Ext.getCmp('logTree').show();
                            Ext.getCmp('logMessage').hide();
                        } else {
                            Ext.getCmp('logMessage').setHtml(message);
                            Ext.getCmp('logTree').hide();
                            Ext.getCmp('logMessage').show();
                        }

                        Ext.getCmp('logTable').store.removeAll();
                        Ext.getCmp('logTable').store.add(resultLogs[logPage].backtrace);
                        document.getElementById('logContent').innerHTML = '';
                    }
                }
            }]
        }]
    }, {
        xtype: 'panel',
        id: 'titleLog',
        width: '100%',
        height: 30,
        bodyStyle: 'color:white; font-weight: bolder; font-size: 15px',
    }, {
        xtype: 'panel',
        height: '25%',
        width: '100%',
        bodyStyle: 'color:white, overflow: auto;',
        items: [ {
            xtype: 'container',
            id: 'logMessage',
            width: '100%',
            height: 180,
            bodyStyle: 'overflow-x:hidden;overflow-y:auto; color: white; font-weight: bolder;font-size: 10px',
        },{
            xtype: 'treepanel',
            id: 'logTree',
            width: '100%',
            height: 180,
            containerScroll: true,
            rootVisible: false,
            bodyStyle: 'color:white, overflow-y: auto;',
            store: Ext.create('Ext.data.TreeStore', {
                root: {
                    id: 'treeLog',
                    expanded: true,
                }
            }),
            listeners: {
                itemclick: function () {
                    this.getView().refresh();
                }
            }
        }],
    }, {
        xtype: 'errorTable',
        height: '25%',
        width: '100%',
        id: 'logTable',
        listeners: {
            itemclick: function (view, rec, node, index, e, options) {
                let that = this;
                setTimeout(function () {
                    let dblclick = parseInt($(that).data('double'), 10);
                    if (dblclick > 0) {
                        $(that).data('double', dblclick - 1);
                    } else {
                        Ext.Ajax.request({
                            url: 'index.php',
                            method: 'POST',
                            params: {
                                project: project,
                                file: rec.data.file,
                                line: rec.data.line,
                                action: 'main.getCode'
                            },
                            dataType: 'json',
                            success: function (data, options) {
                                let regexp = /^{.*}/; //正则表达式判断是否为json串
                                if (!regexp.test(data.responseText)) {
                                    return;
                                }
                                let datum = Ext.decode(data.responseText);
                                document.getElementById('logContent').innerHTML = datum.code;
                            },
                        });
                        that.getView().refresh();
                    }
                }, 300);
            },
            itemdblclick: function (view, rec, node, index, e, options) {
                $(this).data('double', 2);
                Ext.create('phpray.view.main.Code').show();
                codeEditor();
                Ext.Ajax.request({
                    url: 'index.php',
                    method: 'POST',
                    params: {project: project, fileName: rec.data.file, action: "main.fileGetContent"},
                    dataType: 'json',
                    success: function (code, options) {
                        let regexp = /^{.*}/; //正则表达式判断是否为json串
                        if (!regexp.test(code.responseText)) {
                            return;
                        }
                        let response = Ext.decode(code.responseText);
                        if (response) {
                            if (response.error) {
                                alert(response.error);
                                return;
                            }
                            fileName = response.fileName;
                            let title = fileName;
                            if (response.readonly) {
                                title = '(只读) ' + title;
                            }

                            Ext.getCmp('editorWindow').setTitle(title);
                            editor.setReadOnly(response.readonly);
                            Ext.getCmp('save').disable();
                            if (response.readonly || !response.debug) {
                                Ext.getCmp('reverse').disable();
                            } else {
                                Ext.getCmp('reverse').enable();
                            }

                            originContent = response.content;
                            editor.session.setValue(response.content, typeof line === 'number' ? line : 0);
                            let CSS = document.getElementById("editorWindow");
                            CSS.style.display = "block";
                        }
                    }
                });
                this.getView().refresh();
            },
        }
    }, {
        xtype: 'panel',
        id: 'logContent',
        height: '40%',
        bodyStyle: 'background-color: #ccc',
        containerScroll: true,
    }],
});
