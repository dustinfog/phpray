/**
 * This class is the main view for the application. It is specified in app.js as the
 * "mainView" property. That setting automatically applies the "viewport"
 * plugin causing this view to become the body element (i.e., the viewport).
 *
 *
 */
Ext.define('PhpRay.view.main.Main', {
    extend: 'Ext.container.Viewport',
    xtype: 'app-main',
    require: [
        'PhpRay.view.main.menuTree',
        'Ext.plugin.Viewport',
        'Ext.window.MessageBox',
        'PhpRay.view.main.MainController',
        'PhpRay.view.main.MainModel',
        'PhpRay.view.main.ErrorList',
        'PhpRay.view.main.LogList',
        'PhpRay.view.main.PropertyAnalysisList',
        'PhpRay.view.main.ErrorTable',
        'PhpRay.view.main.ErrorWindow',
        'PhpRay.view.main.Code',
        'PhpRay.view.main.ClearCache',
        'PhpRay.view.main.MemoWindow'
    ],
    bodyStyle: 'background-color: #342725',
    layout: 'border',
    items: [{
        region: 'north',
        height: 60,
        viewModel: 'main',
        collapisble: true,
        cls: 'mainPanel',
        bodyStyle: 'background-color: #303030,border:0.1px solid #303030',
        header: {
            title: {
                width: 80,
                cls: 'title',
                flex: 0,
                bind: {
                    text: '{name}',
                },
            },
            items: [{
                xtype: 'combobox',
                cls: 'project',
                id: 'pro',
                width: 160,
                height: 25,
                editable: false,
                flex: 0,
                forceSelection: true,
                autoLoad: false,
                emptyText: '请选择项目',
                fieldStyle:'background-color: #303030; color:white;  font-weight: bolder; border: 0.1px solid #cccc, margin: 0 0 0 0; padding: 0',
                projectList: Ext.Ajax.request({
                    url: 'index.php',
                    params: {action: 'main.getProjects'},
                    method: 'POST',
                    async: false,
                    crossDomain: true,
                    cors: true,
                    useDefaultXhrHeader: false, contentType: "application/json",
                    success: function (response, options) {
                        projectList = Ext.decode(response.responseText);
                        // 连接数据库
                        const request = indexedDB.open('phpRay');
                        request.onupgradeneeded = function (event) {
                            const db = event.target.result;
                            for (let i = 0; i < projectList.length; i++) {
                                db.createObjectStore('History_' + projectList[i], {
                                    keyPath: 'value'
                                });
                                db.createObjectStore(projectList[i], {
                                    keyPath: 'classAndMethod'
                                });
                            }
                            db.createObjectStore('Memo', {
                                keyPath: 'value'
                            });
                            db.close();
                        };
                        let dbRequest = indexedDB.open('phpRay');
                        dbRequest.onsuccess = function (event) {
                            let db = event.target.result;
                            for (let i = 0; i < projectList.length; i++) {
                                db.transaction(['History_' + projectList[i], projectList[i], 'Memo'], 'readwrite');
                            }
                            db.close();
                        };

                        let MemoRequest = indexedDB.open('phpRay');
                        MemoRequest.onsuccess = function (event) {
                            let db = event.target.result;
                            let store = db.transaction('Memo', 'readwrite').objectStore('Memo');
                            let reqAdd = store.add({'value': 'memo', 'memoContent': ''});
                            reqAdd.onsuccess = function (e) {
                                //console.log('init Memo success');
                            };
                            db.close();
                        };
                    },
                }),
                store: projectList,
                listeners: {
                    select: function (combo, record, opts) {
                        project = record.data['field1'];
                        let inData = {action: 'main.getFileTree', project: project};
                        Ext.Ajax.request({
                            url: 'index.php',
                            crossDomain: true,
                            cors: true,
                            useDefaultXhrHeader: false,
                            method: 'POST',
                            params: inData,
                            contentType: "application/json",
                            dataType: 'json',
                            success: function (response, options) {
                                let respText = Ext.decode(response.responseText);
                                Ext.getCmp('ztreeFile').store.getRootNode().removeAll(true);
                                zNodeFile = rootFileData(respText);
                                Ext.getCmp('ztreeFile').store.getNodeById('treeFile').appendChild(zNodeFile);
                                Ext.getCmp('history').store.removeAll();
                                Ext.getCmp('history').setValue();
                                historyDB = 'History_' + project;
                                let historyDataRequest = indexedDB.open('phpRay');
                                historyDataRequest.onsuccess = function (event) {
                                    let db = event.target.result;
                                    let store = db.transaction(historyDB, 'readwrite').objectStore(historyDB);
                                    let reqGet = store.getAll();
                                    reqGet.onsuccess = function (e) {
                                        for (let i = 0; i < e.target.result.length; i++) {
                                            Ext.getCmp('history').store.add({'value': e.target.result[i].value});
                                        }
                                    };
                                    db.close();
                                };
                            },
                            failure: function (jqXHR) {
                                Ext.Msg.alert('运行失败', jqXHR.responseText);
                            }
                        });
                    },
                },
            }, {
                xtype: 'combobox',
                id: 'history',
                height: 25,
                margin: '0 20',
                editable: false,
                flex: 1,
                blankText: 'history',
                emptyText: 'history',
                fieldStyle:'background-color: #303030; color:white; font-weight: bolder; border: 0.1px solid #cccc, margin: 0 0 0 0; padding: 0',
                store: Ext.create('Ext.data.Store', {
                    fields: ['value'],
                    data: []
                }),
                displayField: 'value',
                listeners: {
                    select: function (combo, record, opts) {
                        historyValue = record.data['value'];
                        className = historyValue.split('::')[0];
                        methodName = historyValue.split('::')[1];
                        fileName = historyValue.split('\\')[1].match(/(\S*)::/)[0] + '.php';
                        let dbRequest = indexedDB.open('phpRay');
                        dbRequest.onsuccess = function (e) {
                            let db = e.target.result;
                            let store = db.transaction(project, 'readwrite').objectStore(project);
                            let reqGet = store.get(record.data['value']);
                            reqGet.onsuccess = function (event) {
                                let eventData = event.target.result;
                                if (eventData) {
                                    if (eventData.initCode === null && eventData.testCode === null) {
                                        getTestCode();
                                    } else if (eventData.initCode === null) {
                                        Ext.Ajax.request({
                                            url: 'index.php',
                                            method: 'POST',
                                            params: {
                                                project: project,
                                                fileName: fileName,
                                                action: 'main.getTestCode',
                                                methodName: methodName,
                                                className: className
                                            },
                                            dataType: 'json',
                                            success: function (data, options) {
                                                let obj = Ext.decode(data.responseText);
                                                classCode = obj.classCode;
                                                codeEditorInit('<?php' + '\r' + classCode);
                                            },
                                        });
                                        methodCode = eventData.initCode;
                                        codeEditorTest('<?php' + '\r' + eventData.testCode);
                                    } else if (eventData.testCode === null) {
                                        Ext.Ajax.request({
                                            url: 'index.php',
                                            crossDomain: true,
                                            cors: true,
                                            useDefaultXhrHeader: false,
                                            method: 'POST',
                                            params: {
                                                project: project,
                                                fileName: fileName,
                                                action: 'main.getTestCode',
                                                methodName: methodName,
                                                className: className
                                            },
                                            contentType: "application/json",
                                            dataType: 'json',
                                            success: function (data, options) {
                                                let obj = Ext.decode(data.responseText);
                                                methodCode = obj.methodCode;
                                                codeEditorTest('<?php' + '\r' + obj.methodCode);
                                            },
                                        });
                                        classCode = eventData.initCode;
                                        codeEditorInit('<?php' + '\r' + eventData.initCode);
                                    } else {
                                        methodCode = eventData.testCode;
                                        classCode = eventData.initCode;
                                        codeEditorInit('<?php' + '\r' + eventData.initCode);
                                        codeEditorTest('<?php' + '\r' + eventData.testCode);
                                    }
                                } else {
                                    getTestCode();
                                }
                            };

                            db.close();
                        };
                    },
                }
            }, {
                xtype: 'button',
                cls: 'btn',
                id: 'run',
                iconCls: 'run',
                margin: '0 5',
                flex: 0,
                text: '运行',
                listeners: {
                    click: function () {
                        if (editorTest || editorInit) {
                            methodCode = editorTest.session.getValue().split('<?php' + '\r')[1];
                            classCode = editorInit.session.getValue().split('<?php' + '\r')[1];
                        } else {
                            methodCode = Ext.getCmp('testCode').lastValue;
                            classCode = Ext.getCmp('initCode').lastValue;
                        }
                        if (!methodCode) {
                            let info = '测试代码不能为空！！！';
                            Ext.getCmp('output').setHtml(info);
                            return;
                        }
                        let addRequest = indexedDB.open('phpRay');
                        addRequest.onsuccess = function (event) {
                            let db = event.target.result;
                            let store = db.transaction(project, 'readwrite').objectStore(project);
                            let reqAdd = store.put({
                                'classAndMethod': className + '::' + methodName,
                                'initCode': classCode,
                                'testCode': methodCode
                            });
                            reqAdd.onsuccess = function (e) {
                                //console.log('测试代码保存成功');
                            };
                            db.close();
                        };
                        Ext.getCmp('run').setStyle('cursor', 'wait');
                        Ext.getCmp('run').disable();
                        Ext.getCmp('stop').enable();
                        stopAjax = Ext.Ajax.request({
                            url: 'index.php',
                            method: 'POST',
                            params: {
                                methodCode: methodCode,
                                classCode: classCode,
                                project: project,
                                className: className,
                                fileName: fileName,
                                action: 'main.runTest',
                            },
                            dataType: 'json',
                            async: true,
                            timeout: 30000,
                            success: function (response, options) {
                                Ext.getCmp('error').store.removeAll();
                                Ext.getCmp('log').store.removeAll();
                                Ext.getCmp('profile').store.removeAll();
                                Ext.getCmp('return').store.removeAll();
                                let regexp = /^{.*}/; //正则表达式判断是否为json串
                                let retBool = regexp.test(response.responseText);
                                if (!retBool) {
                                    Ext.getCmp('output').setHtml(response.responseText);
                                    Ext.getCmp('stop').disable();
                                    Ext.getCmp('run').enable();
                                    Ext.getCmp('run').setStyle('cursor', 'pointer');
                                    return;
                                }
                                let respText = Ext.decode(response.responseText);
                                let resultReturn = respText.return;
                                let resultOutput = respText.output;
                                resultError = respText.errors;
                                let resultElapsed = respText.elapsed;
                                let resultLogs = respText.logs;
                                let resultProfile = respText.profileData;
                                let returnData = returnRootData(resultReturn);
                                Ext.getCmp('return').store.add(returnData);
                                Ext.getCmp('output').setHtml(resultOutput);
                                Ext.getCmp('elapsedTime').setHtml(resultElapsed);

                                //性能分析数据格式处理
                                function ProfileObj(callee, caller, ct, wt, CPU, mu, pmu) {
                                    this.callee = callee;
                                    this.caller = caller;
                                    this.ct = ct;
                                    this.wt = wt;
                                    this.CPU = CPU;
                                    this.mu = mu;
                                    this.pmu = pmu;
                                }

                                for (let i in resultProfile) {
                                    let profile = new ProfileObj(resultProfile[i]['callee'], resultProfile[i]['caller'], resultProfile[i]['ct'], resultProfile[i]['wt'], resultProfile[i]['cpu'], resultProfile[i]['mu'], resultProfile[i]['pmu']);
                                    Ext.getCmp('profile').store.add(profile);
                                }

                                //错误数据格式处理
                                for (let i in resultError) {
                                    let error = new ErrorObj(resultError[i]['type'], resultError[i]['file'], resultError[i]['message'], resultError[i]['line']);
                                    Ext.getCmp('error').store.add(error);
                                }

                                //日志数据格式处理
                                function LogObj(recorder, message) {
                                    this.recorder = recorder;
                                    this.message = message;
                                }

                                for (let i in resultLogs) {
                                    let log = new LogObj(resultLogs[i]['logger'], resultLogs[i]['message']);
                                    Ext.getCmp('log').store.add(log);
                                }
                                Ext.getCmp('stop').disable();
                                Ext.getCmp('run').enable();
                                Ext.getCmp('run').setStyle('cursor', 'pointer');
                            },
                            failure: function (jqXHR) {
                                Ext.getCmp('run').setStyle('cursor', 'pointer');
                                Ext.getCmp('run').enable();
                                Ext.getCmp('stop').disable();
                            }
                        });
                    }
                }
            }, {
                xtype: 'button',
                cls: 'btn',
                id: 'stop',
                iconCls: 'stop',
                margin: '0 5',
                flex: 0,
                text: '停止',
                disabled: true,
                listeners: {
                    click: function () {
                        Ext.Ajax.abort(stopAjax);
                        Ext.getCmp('stop').disable();
                    }
                }
            }, {
                xtype: 'button',
                cls: 'btn',
                iconCls: 'refresh',
                margin: '0 5',
                flex: 0,
                text: '刷新',
                listeners: {
                    click: function () {
                        Ext.getCmp('search').setValue('');
                    }
                }
            }, {
                xtype: 'button',
                cls: 'btn',
                iconCls: 'clearCache',
                margin: '0 5',
                flex: 0,
                text: '清空缓存',
                listeners: {
                    click: function () {
                        Ext.create('PhpRay.view.main.ClearCache').show();
                    }
                }
            }, {
                xtype: 'button',
                cls: 'btn',
                iconCls: 'memo',
                margin: '0 5',
                flex: 0,
                text: '备忘录',
                listeners: {
                    click: function () {
                        let request = indexedDB.open('phpRay');
                        request.onsuccess = function (event) {
                            let db = event.target.result;
                            let store = db.transaction('Memo', 'readwrite').objectStore('Memo');
                            let reqGet = store.get('memo');
                            reqGet.onsuccess = function (e) {
                                Ext.getCmp('memoContent').setValue(e.target.result.memoContent);
                            };
                            db.close();
                        };
                        Ext.create('PhpRay.view.main.MemoWindow').show();
                    }
                }
            }],
        },
    }, {
        region: 'west',
        cls: 'mainPanel',
        split: true,
        width: 250,
        layout: 'border',
        bodyStyle: 'margin:10 10 10 10',
        items: [{
            region: 'center',
            split: true,
            layout: 'border',
            items: [{
                region: 'north',
                xtype: 'panel',
                id: 'fileTitle',
                bodyStyle: "background-color:#3D3F41;padding:10 0 10 10;font-size:15px;font-weight:bolder;color:white;border:1px solid #ccc",
                height: 30,
                html: '文件',
            }, {
                region: 'center',
                xtype: 'treepanel',
                id: 'ztreeFile',
                cls: 'fileTree',
                height: '85%',
                store: Ext.create('Ext.data.TreeStore', {
                    root: {
                        id: 'treeFile',
                        mixins: ['MyExtend.lib.TreeFilter'],
                        expanded: true,
                    }
                }),
                lines: true,
                containerScroll: true,
                rootVisible: false,
                singleExpand: false,
                "dockedItems": [{
                    xtype: 'toolbar',
                    dock: 'top',
                    style: 'background-color:#303030;',
                    items: [{
                        xtype: 'textfield',
                        width: '100%',
                        id: 'search',
                        fieldStyle:'background-color: #3C3F41; color:white; font-weight: bolder;border:#303030',
                        bodyStyle: 'background: black; color: white',
                        listeners: {
                            change: function (field, newVal) {
                                let reportBuilderStore = field.up('panel').getStore();
                                if (!Ext.isEmpty(field.value)) {
                                    reportBuilderStore.filterBy(function (rec) {
                                        let childs = !Ext.isEmpty(rec.get('children')) ? rec.get('children').map(function (x) {
                                            return x.text;
                                        }) : [];
                                        let matched = false;
                                        for (let val of childs) {
                                            if (val.toUpperCase().match((field.value).toUpperCase())) {
                                                matched = true;
                                                break;
                                            }
                                        }
                                        if (!Ext.isEmpty(rec.get('text').toUpperCase().match((field.value).toUpperCase())) || rec.get('text').toUpperCase() === "ROOT" || matched)
                                            return true;
                                    });
                                } else {
                                    reportBuilderStore.clearFilter();
                                }
                            },
                            buffer: 250
                        }
                    }]
                }],
                listeners: {
                    itemclick: function (node, e) {
                        if (e.data.leaf === true) {
                            let that = this;
                            setTimeout(function () {
                                var dblclick = parseInt($(that).data('double'), 10);
                                if (dblclick > 0) {
                                    $(that).data('double', dblclick - 1);
                                } else {
                                    if (e.data.text === Ext.getCmp('search').getValue()) {
                                        return;
                                    }
                                    fileName = e.data.text;
                                    getFileMethod();
                                    that.getSelectionModel().clearSelections();
                                    that.getView().refresh();
                                }
                            }, 300);
                        }


                    },
                    itemdblclick: function (node, e) {
                        $(this).data('double', 2);
                        fileName = e.data.text;
                        if (e.data.leaf === true) {
                            Ext.create('PhpRay.view.main.Code').show();
                            codeEditor();
                            edit();
                        }
                        this.getSelectionModel().clearSelections();
                        this.getView().refresh();
                    }
                }
            }]

        }, {
            region: 'south',
            height: '50%',
            layout: 'border',
            items: [{
                region: 'north',
                xtype: 'panel',
                id: 'daGangTitle',
                bodyStyle: "background-color: #3D3F41; padding:10 0 10 10;font-size:15px;font-weight:bolder;border:0.1px solid #ccc; color: white ",
                height: 30,
                html: '大纲',
            }, {
                region: 'center',
                xtype: 'treepanel',
                id: 'ztreeMethod',
                cls: 'fileTree',
                height: '85%',
                store: Ext.create('Ext.data.TreeStore', {
                    root: {
                        id: 'treeMethod',
                        unstyled: true,
                        expanded: true,
                    }
                }),
                "dockedItems": [{
                    xtype: 'toolbar',
                    dock: 'top',
                    style: 'background-color:#303030;',
                    items: [{
                        xtype: 'textfield',
                        width: '100%',
                        id: 'methodSearch',
                        fieldStyle:'background-color: #3C3F41; color:white; font-weight: bolder; border:#303030',
                        listeners: {
                            change: function (field, newVal) {
                                let reportBuilderStore = field.up('panel').getStore();
                                if (!Ext.isEmpty(field.value)) {
                                    reportBuilderStore.filterBy(function (rec) {
                                        let childs = !Ext.isEmpty(rec.get('children')) ? rec.get('children').map(function (x) {
                                            return x.text;
                                        }) : [];
                                        let matched = false;
                                        for (let val of childs) {
                                            if (val.toUpperCase().match((field.value).toUpperCase())) {
                                                matched = true;
                                                break;
                                            }
                                        }
                                        if (!Ext.isEmpty(rec.get('text').toUpperCase().match((field.value).toUpperCase())) || rec.get('text').toUpperCase() === "ROOT" || matched)
                                            return true;
                                    });
                                } else {
                                    reportBuilderStore.clearFilter();
                                }
                            },
                            buffer: 250
                        }
                    }]
                }],
                lines: true,
                containerScroll: true,
                rootVisible: false,
                singleExpand: false,
                listeners: {
                    itemclick: function (node, e) {
                        if (e.data.leaf === true) {
                            methodName = e.data.text.split('(')[0];
                            getTestCode();
                            this.getSelectionModel().clearSelections();
                            this.getView().refresh();
                        }
                    },
                }
            }]
        }]
    }, {
        region: 'center',
        layout: 'border',
        items: [{
            region: 'north',
            split: true,
            collapisble: true,
            layout: 'border',
            height: 200,
            items: [{
                region: 'north',
                xtype: 'panel',
                id: 'initCodeTitle',
                bodyStyle: "background-color:#3D3F41;font-size:15px;font-weight:bolder;color:white; border: 0.1px solid #ccc",
                height: 30,
                html: '初始化代码',
            }, {
                region: 'center',
                xtype: 'textarea',
                height: 170,
                width: '100%',
                id: 'initCode',
                disabled: false
            }],
        }, {
            region: 'center',
            split: true,
            collapisble: true,
            height: 200,
            items: [{
                region: 'north',
                xtype: 'panel',
                id: 'testCodeTitle',
                bodyStyle: "background-color: #3D3F41;font-size:15px;font-weight:bolder;color:white; border: 0.1px solid #ccc",
                height: 30,
                html: '测试代码',
            }, {
                xtype: 'textarea',
                height: '100%',
                width: '100%',
                id: 'testCode',
            }],
        }, {
            region: 'south',
            xtype: 'tabpanel',
            height: 300,
            controller: 'main',
            viewModel: 'main',
            ui: 'navigation',
            panel: true,
            deferredRender: false,
            split: true,
            items: [{
                title: '返回',
                iconCls: 'return',
                xtype: 'treepanel',
                id: 'return',
                height: '100%',
                width: '100%',
                containerScroll: true,
                rootVisible: false,
                store: Ext.create('Ext.data.TreeStore', {
                    root: {
                        id: 'treeReturn',
                        expanded: true,
                    }
                }),
                listeners: {
                    itemclick: function () {
                        this.getSelectionModel().clearSelections();
                        this.getView().refresh();
                    }
                }
            }, {
                title: '输出',
                iconCls: 'output',
                xtype: 'panel',
                height: '100%',
                width: '100%',
                id: 'output',
                editable: false,
                bodyStyle: 'overflow-x:hidden;overflow-y:auto; color: white;font-weight: bolder',
            }, {
                title: '耗时',
                iconCls: 'elapsedTime',
                bodyStyle: 'overflow-x:hidden;overflow-y:auto; color: white; font-weight: bolder',
                height: '100%',
                width: '100%',
                id: 'elapsedTime',
                xtype: 'panel',
                editable: false,
            }, {
                title: '错误',
                iconCls: 'error',
                bodyStyle: 'overflow-x:hidden;overflow-y:auto;',
                enableColumnMove: true,
                items: [{
                    id: 'error',
                    xtype: 'errorList',
                    listeners: {
                        itemclick: function (view, rec, node, index, e, options) {
                            Ext.create('PhpRay.view.main.ErrorWindow').show();
                            errorTotalPage = Ext.getCmp('error').getStore().getCount();
                            errorPage = index;
                            Ext.getCmp('errorPage').setText((errorPage + 1) + '/' + errorTotalPage); //页数
                            Ext.getCmp('titleError').setHtml('错误类型:  ' + rec.data.type);
                            if (resultError[errorPage].exception) {
                                let errorData = returnRootData(resultError[errorPage].exception);
                                Ext.getCmp('errorTree').store.getNodeById('treeError').removeAll(true);
                                Ext.getCmp('errorTree').store.getNodeById('treeError').appendChild(errorData);
                            } else {
                                document.getElementById('errorTree').innerHTML = rec.data.message;
                            }
                            Ext.getCmp('errorTable').store.removeAll();
                            Ext.getCmp('errorTable').store.add(new ErrorTableObj(resultError[errorPage].file, resultError[errorPage].line));
                            Ext.getCmp('errorTable').store.add(resultError[errorPage].backtrace);
                            this.getSelectionModel().clearSelections();
                            this.getView().refresh();
                        },
                    }
                }]
            }, {
                title: '日志',
                iconCls: 'log',
                bodyStyle: 'overflow-x:hidden;overflow-y:auto;',
                items: [{
                    id: 'log',
                    xtype: 'logList',
                    listeners: {
                        select: function () {
                            this.getSelectionModel().clearSelections();
                            this.getView().refresh();
                        },
                    }
                }]
            }, {
                title: '性能分析',
                iconCls: 'profile',
                bodyStyle: 'overflow-x:hidden;overflow-y:auto;',
                items: [{
                    xtype: 'PropertyAnalysisList',
                    id: 'profile',
                    listeners: {
                        select: function () {
                            this.getSelectionModel().clearSelections();
                            this.getView().refresh();
                        },
                    }
                }]
            }]
        }],
    }]
});