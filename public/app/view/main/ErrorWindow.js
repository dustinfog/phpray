Ext.define('phpray.view.main.ErrorWindow', { //错误弹窗
    extend: 'Ext.window.Window',
    height: 758,
    width: 1024,
    layout: {
        type: 'vbox',
        align: 'stretch' //拉伸使其充满整个父容器
    },
    title: '错误详情',
    modal: true, //背景变灰
    id: 'errorWindow',
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
                        if (errorPage <= 0) {
                            return;
                        }
                        errorPage--;
                        Ext.getCmp('errorPage').setText((errorPage + 1) + '/' + errorTotalPage); //页数
                        let reg = /^[0-9]*$/;
                        let type = resultError[errorPage].type;
                        if (reg.test(type)) {
                            type = errorType[type]; //错误类型解析
                        }
                        Ext.getCmp('titleError').setHtml('错误类型:  ' + type);
                        Ext.getCmp('errorTree').store.getNodeById('treeError').removeAll(true);
                        if (resultError[errorPage].exception) {
                            let errorData = returnRootData(resultError[errorPage].exception);
                            Ext.getCmp('errorTree').store.getNodeById('treeError').appendChild(errorData);
                            Ext.getCmp('errorTree').expandAll();
                            Ext.getCmp('errorTree').show();
                            Ext.getCmp('errorMessage').hide();
                        } else {
                            Ext.getCmp('errorMessage').setHtml(resultError[errorPage].message);
                            Ext.getCmp('errorTree').hide();
                            Ext.getCmp('errorMessage').show();
                        }
                        Ext.getCmp('errorTable').store.removeAll();
                        Ext.getCmp('errorTable').store.add(new ErrorTableObj(resultError[errorPage].file, resultError[errorPage].line));
                        Ext.getCmp('errorTable').store.add(resultError[errorPage].backtrace);
                    }
                }
            }, {
                xtype: 'button',
                id: 'errorPage',
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
                        if (errorPage >= errorTotalPage - 1) {
                            return;
                        }
                        errorPage++;
                        Ext.getCmp('errorPage').setText((errorPage + 1) + '/' + errorTotalPage); //页数
                        let reg = /^[0-9]*$/;
                        let type = resultError[errorPage].type;
                        if (reg.test(type)) {
                            type = errorType[type]; //错误类型解析
                        }
                        Ext.getCmp('titleError').setHtml('错误类型:  ' + type);
                        Ext.getCmp('errorTree').store.getNodeById('treeError').removeAll(true);
                        if (resultError[errorPage].exception) {
                            let errorData = returnRootData(resultError[errorPage].exception);
                            Ext.getCmp('errorTree').store.getNodeById('treeError').appendChild(errorData);
                            Ext.getCmp('errorTree').expandAll();
                            Ext.getCmp('errorTree').show();
                            Ext.getCmp('errorMessage').hide();
                        } else {
                            Ext.getCmp('errorMessage').setHtml(resultError[errorPage].message);
                            Ext.getCmp('errorTree').hide();
                            Ext.getCmp('errorMessage').show();
                        }
                        Ext.getCmp('errorTable').store.removeAll();
                        Ext.getCmp('errorTable').store.add(new ErrorTableObj(resultError[errorPage].file, resultError[errorPage].line));
                        Ext.getCmp('errorTable').store.add(resultError[errorPage].backtrace);
                    }
                }
            }]
        }]
    }, {
        xtype: 'panel',
        id: 'titleError',
        width: '100%',
        height: 30,
        bodyStyle: 'color:white; font-weight: bolder; font-size: 15px',
    }, {
        xtype: 'panel',
        height: '25%',
        width: '100%',
        bodyStyle: 'color:white, overflow: auto;',
        items: [{
            xtype: 'container',
            id: 'errorMessage',
            width: '100%',
            height: '180',
            bodyStyle: 'overflow-x:hidden;overflow-y:auto; color: white; font-weight: bolder;font-size: 10px',
            containerScroll: true,
        }, {
            xtype: 'treepanel',
            id: 'errorTree',
            width: '100%',
            height: '180',
            containerScroll: true,
            rootVisible: false,
            bodyStyle: 'color:white',
            store: Ext.create('Ext.data.TreeStore', {
                root: {
                    id: 'treeError',
                    expanded: true,
                }
            }),
            listeners: {
                itemclick: function () {
                    this.getView().refresh();
                }
            }
        }]
    }, {
        xtype: 'errorTable',
        height: '25%',
        width: '100%',
        id: 'errorTable',
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
                                document.getElementById('errorContent').innerHTML = datum.code;
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
        id: 'errorContent',
        height: '40%',
        // bodyStyle: 'background-color: #ccc',
        containerScroll: true,
    }],
});
