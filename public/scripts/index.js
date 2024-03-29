let projectList = [];
let project = ''; //当前项目
let fileName = ''; //当前文件名
let className = ''; //当前class名
let methodName = ''; //当前方法名
let stopAjax;// 运行代码的ajax请求,(用于页面停止ajax请求)
let methodCode = ''; //当前测试代码
let classCode = ''; //当前初始化代码
let zNodeFile;//当前文件树数据
let zNodeMethod; //当前方法树数据
let resultError = []; //运行后，返回的error数据
let historyDB; //当前的历史数据表名
let historyValue = ''; //当前history文件方法
let errorPage = 0; //当前选择的error序号
let errorTotalPage = 0; //error总条数
let resultLogs = []; //运行后，返回的log数据
let logPage = 0; //当前选择的error序号
let logTotalPage = 0; //log总条数
let logStore; //日志grid数据
let errorStore; //错误grid数据
let allowModify = false; //是否允许代码预览

function isAllowModify() {
    Ext.Ajax.request({
        url: 'index.php',
        params: {action: 'main.allowModify'},
        method: 'POST',
        async: false,
        crossDomain: true,
        cors: true,
        useDefaultXhrHeader: false, contentType: "application/json",
        success: function (response, options) {
            allowModify = Ext.decode(response.responseText);
        }
    });
}



//获得测试代码
function getTestCode() {
    let addRequest = indexedDB.open('phpRay');
    addRequest.onsuccess = function (event) {
        let db = event.target.result;
        let store = db.transaction(project, 'readwrite').objectStore(project);
        let reqGet = store.get(className + '::' + methodName);
        reqGet.onsuccess = function (event) {
            let eventData = event.target.result;
            if (typeof eventData === 'undefined') {
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
                        methodCode = obj.methodCode;
                        editorTest.session.setValue('<?php' + '\r' + methodCode);
                        editorInit.session.setValue('<?php' + '\r' + classCode);
                        let request = indexedDB.open('phpRay');
                        request.onsuccess = function (event) {
                            let db = event.target.result;
                            let tx = db.transaction(historyDB, 'readwrite');
                            let store = tx.objectStore(historyDB);
                            let reqAddHistory = store.add({'value': className + '::' + methodName});
                            reqAddHistory.onsuccess = function (event) {
                                let reqGet = store.getAll();
                                reqGet.onsuccess = function (event) {
                                    Ext.getCmp('history').store.removeAll();
                                    for (let i = 0; i < event.target.result.length; i++) {
                                        Ext.getCmp('history').store.add({'value': event.target.result[i].value});
                                    }
                                };
                            };

                            let storeProject = db.transaction(project, 'readwrite').objectStore(project);
                            let reqAddProject = storeProject.put({
                                'classAndMethod': className + '::' + methodName,
                                'initCode': classCode,
                                'testCode': methodCode,
                                'fileName': fileName,
                                'className': className,
                                'methodName': methodName
                            });
                            reqAddProject.onsuccess = function (e) {
                                //console.log('测试代码保存成功');
                            };

                            db.close();
                        };
                        Ext.getCmp('history').setValue(className + '::' + methodName);
                        historyValue = className + '::' + methodName;

                    },
                });

            } else {
                if (eventData.initCode === null || eventData.testCode === null) {
                    Ext.Ajax.request({
                        url: 'index.php',
                        method: 'POST',
                        params: {
                            project: project,
                            fileName: fileName,
                            action: 'main.getTestCode',
                            methodName: eventData.methodName,
                            className: eventData.className
                        },
                        dataType: 'json',
                        success: function (data, options) {
                            let obj = Ext.decode(data.responseText);
                            classCode = obj.classCode;
                            methodCode = obj.methodCode;
                            editorTest.session.setValue('<?php' + '\r' + (eventData.testCode === null ? methodCode : eventData.testCode));
                            editorInit.session.setValue('<?php' + '\r' + (eventData.initCode === null ? classCode : eventData.initCode));
                            Ext.getCmp('history').setValue(eventData.className + '::' + eventData.methodName);
                            historyValue = eventData.className + '::' + eventData.methodName;
                        }
                    });
                } else {
                    editorTest.session.setValue('<?php' + '\r' + eventData.testCode);
                    editorInit.session.setValue('<?php' + '\r' + eventData.initCode);
                    Ext.getCmp('history').setValue(eventData.className + '::' + eventData.methodName);
                    historyValue = eventData.className + '::' + eventData.methodName;
                }
            }
        };
        db.close();
    };
}

//获得文件中的方法
function getFileMethod() {
    if (!fileName) {
        return;
    }
    Ext.Ajax.request({
        url: 'index.php',
        method: 'POST',
        params: {project: project, fileName: fileName, action: 'main.getClassesAndMethods'},
        dataType: 'json',
        success: function (data, options) {
            Ext.getCmp('ztreeMethod').store.getNodeById('treeMethod').removeAll(true);
            editorTest.session.setValue('<?php' + '\r');
            editorInit.session.setValue('<?php' + '\r');
            Ext.getCmp('history').setValue('');
            Ext.getCmp('methodSearch').setValue();
            let response = Ext.decode(data.responseText);
            if (response.length === 0) {
                return;
            }
            className = response[0].name;
            zNodeMethod = rootMethodData(response[0]);
            Ext.getCmp('ztreeMethod').store.getNodeById('treeMethod').appendChild(zNodeMethod);
            Ext.getCmp('ztreeMethod').expandAll();
        },
    });
}

//代码内容编辑弹窗
let editor = null;
let changeDetectTimer = 0;
let originContent;
let focusInEditor = false;

function codeEditor() {
    editor = ace.edit("editor");
    editor.setTheme("ace/theme/monokai");
    editor.session.setMode("ace/mode/php");
    editor.moveCursorTo(0, 0);
    editor.on("focus", function (o) {
        focusInEditor = true;
    });
    editor.on("blur", function (o) {
        focusInEditor = false;
    });
    editor.on('change', function (o) {
        if (changeDetectTimer !== 0) {
            return;
        }
        changeDetectTimer = setTimeout(function () {
            if (originContent === editor.getValue()) {
                Ext.getCmp('save').disable();
            } else {
                Ext.getCmp('save').enable();
            }

            changeDetectTimer = 0;
        }, 1000);
        document.onkeydown = function (ev) {
            let currKey = ev.keyCode || ev.which || ev.charCode;
            if (currKey === 83 && (ev.ctrlKey || ev.metaKey)) {
                ev.preventDefault();
                if (!focusInEditor) {
                    return;
                }
                save();
            }
        }
    });
}

function edit(line) {
    Ext.Ajax.request({
        url: 'index.php',
        method: 'POST',
        params: {project: project, fileName: fileName, action: "main.fileGetContent"},
        dataType: 'json',
        success: function (code, options) {
            let regexp = /^{.*}/; //正则表达式判断是否为json串
            let retBool = regexp.test(code.responseText);
            if (retBool === false) {
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
}

function save() {
    if (editor.getValue() === originContent) {
        return;
    }
    Ext.Ajax.request({
        url: 'index.php',
        method: 'POST',
        params: {project: project, fileName: fileName, action: 'main.filePutContent', content: editor.getValue()},
        dataType: 'json',
        success: function (code, options) {
            let response = Ext.decode(code.responseText);
            if (response) {
                if (response.error) {
                    alert(response.error);
                    return;
                }
                originContent = editor.getValue();
                Ext.getCmp('save').disable();
                Ext.getCmp('reverse').enable();
            }
        }
    });
}

function clearSelectCode(data) {
    if (data === 'clearInitCode') {
        let request = indexedDB.open('phpRay');
        request.onsuccess = function (event) {
            let db = event.target.result;
            let store = db.transaction(project, 'readwrite').objectStore(project);
            let reqGet = store.get(historyValue);
            reqGet.onsuccess = function (e) {
                if (typeof e.target.result !== 'undefined') {
                    let reqAdd = store.put({
                        classAndMethod: historyValue,
                        initCode: null,
                        testCode: e.target.result.testCode,
                        fileName: e.target.result.fileName,
                        className: e.target.result.className,
                        methodName: e.target.result.methodName
                    });
                    reqAdd.onsuccess = function (eve) {
                        Ext.Msg.alert('Success', "清除当前初始化代码成功！！");
                    };
                }
            };
            db.close();
        };
    } else if (data === 'clearTestCode') {
        let request = indexedDB.open('phpRay');
        request.onsuccess = function (event) {
            let db = event.target.result;
            let store = db.transaction(project, 'readwrite').objectStore(project);
            let reqGet = store.get(historyValue);
            reqGet.onsuccess = function (eve) {
                if (typeof eve.target.result !== 'undefined') {
                    let reqAdd = store.put({
                        classAndMethod: historyValue,
                        initCode: eve.target.result.initCode,
                        testCode: null,
                        fileName: eve.target.result.fileName,
                        className: eve.target.result.className,
                        methodName: eve.target.result.methodName
                    });
                    reqAdd.onsuccess = function (e) {
                        Ext.Msg.alert('Success', "清除当前测试代码成功！！");
                    };
                }
            };
            db.close();
        };
    } else if (data === 'clearTestAndInitCode') {
        let request = indexedDB.open('phpRay');
        request.onsuccess = function (event) {
            let db = event.target.result;
            let store = db.transaction(project, 'readwrite').objectStore(project);
            let reqGet = store.get(historyValue);
            reqGet.onsuccess = function (eve) {
                if (typeof eve.target.result !== 'undefined') {
                    let reqAdd = store.put({
                        classAndMethod: historyValue,
                        initCode: null,
                        testCode: null,
                        fileName: eve.target.result.fileName,
                        className: eve.target.result.className,
                        methodName: eve.target.result.methodName
                    });
                    reqAdd.onsuccess = function (e) {
                        Ext.Msg.alert('Success', "清除当前初始化和测试代码成功！！");
                    };
                }
            };
            db.close();
        };
    } else {
        if (!project) {
            Ext.Msg.alert('Failed', '请先选择项目');
            return;
        }
        let request = indexedDB.open('phpRay');
        request.onsuccess = function (event) {
            let db = event.target.result;
            let store = db.transaction(project, 'readwrite').objectStore(project);
            let reqClear = store.clear();
            reqClear.onsuccess = function (e) {
            };
            for (let i = 0; i < projectList.length; i++) {
                let storeHistory = db.transaction('History_' + projectList[i], 'readwrite').objectStore('History_' + projectList[i]);
                let reqHistoryClear = storeHistory.clear();
                reqHistoryClear.onsuccess = function (e) {

                };
            }
            Ext.getCmp('history').store.removeAll();
            Ext.getCmp('history').setValue('');
            editorTest.session.setValue('<?php' + '\r');
            editorInit.session.setValue('<?php' + '\r');
            Ext.Msg.alert('Success', "清除当前初始化和测试代码成功！！");
            db.close();
        };
    }
}

//初始化代码
let editorInit = null;

function codeEditorInit(data) {
    editorInit = ace.edit("initCode");
    editorInit.setTheme("ace/theme/twilight");
    editorInit.session.setMode("ace/mode/php");
    editorInit.moveCursorTo(0, 0);
    editorInit.session.setValue(data, typeof line === 'number' ? line : 0);
    let CSS = document.getElementById("initCode");
    CSS.style.display = "block";
}

//测试代码
let editorTest = null;

function codeEditorTest(data) {
    editorTest = ace.edit("testCode");
    editorTest.setTheme("ace/theme/twilight");
    editorTest.session.setMode("ace/mode/php");
    editorTest.moveCursorTo(0, 0);
    editorTest.session.setValue(data, typeof line === 'number' ? line : 0);
    let CSS = document.getElementById("testCode");
    CSS.style.display = "block";
}


//数据处理成树形结构识别的对象
function DataObj(text, children = null, leaf = true, iconCls, description = null) {
    this.text = text;
    this.children = children;
    this.leaf = leaf;
    this.iconCls = iconCls;
    this.description = description;
}

// 文件的树形数据结构处理
function rootFileData(Data) {
    let result = [];
    for (let j in Data) {
        let Data1 = Data[j];
        let data = [];
        data['text'] = '';
        for (let i in Data1) {
            if (i !== 'children') {
                if (i === 'name') {
                    let arr = Data1[i].split('/');
                    data['text'] = arr[arr.length - 1];
                } else if (i === 'isBranch') {
                    data['leaf'] = !Data1[i];
                }
            } else if (i === 'children') {
                if (Data1[i] === null) continue;
                data['leaf'] = false;
                data['iconCls'] = 'icon-file';
                data['children'] = rootFileData(Data1['children']);
            }
        }
        result[j] = new DataObj(data['text'], data['children'], data['leaf'], data['iconCls'] ? data['iconCls'] : 'icon-file-leaf');

    }
    return result;
}

function rootMethodLeafData(Data) {
    let result = [];
    for (let j in Data) {
        let DataNum = Data[j];
        let data = [];
        data['text'] = DataNum['name'];
        data['leaf'] = !DataNum['isBranch'];
        data['description'] = DataNum['description'];
        data['iconCls'] = '';
        if (DataNum['accessible'] === 1) {
            data['iconCls'] = 'icon-method-public';
        } else if (DataNum['accessible'] === 3) {
            data['iconCls'] = 'icon-method-private';
        } else if (DataNum['accessible'] === 2) {
            data['iconCls'] = 'icon-method-protected';
        }
        if (DataNum['isStatic']) {
            data['iconCls'] += '-static';
        }
        if (DataNum['isInherent']) {
            data['iconCls'] += '-inherent';
        }
        if (DataNum['isConstructor']) {
            data['iconCls'] += '-constructor';
        }
        result[j] = new DataObj(data['text'], data['children'], data['leaf'], data['iconCls'], data['description']);
    }

    return result;
}

//大纲(方法)的树形数据结构处理
function rootMethodData(Data) {
    let data = [];
    data['text'] = '';
    for (let i in Data) {
        if (i !== 'children') {
            if (i === 'name') {
                data['text'] = Data[i];
            } else if (i === 'isBranch') {
                data['leaf'] = !Data[i];
            } else if (i === 'description') {
                data['description'] = Data[i];
            }
        } else if (i === 'children') {
            if (Data[i] === null) continue;
            data['leaf'] = false;
            data['iconCls'] = 'icon-method';
            data['children'] = rootMethodLeafData(Data['children']);
        }
    }
    return new DataObj(data['text'], data['children'], data['leaf'], data['iconCls'], data['description']);
}

//处理return和error数据格式，使treepanel可以识别
function ReturnChildData(Data) {
    let childArr = [];
    for (let i in Data) {
        let data = returnRootData(Data[i]);
        childArr.push(new DataObj(data.text, data.children, data.leaf, data.iconCls));
    }
    return childArr;
}

function returnRootData(Data) {
    let data = [];
    data['text'] = '';
    for (let i in Data) {
        if (i !== 'children') {
            if (i === 'name') {
                data['text'] = '<font style="color:#EDD99A">' + Data[i] + '</font>' + '<font style="color:#deb887"> => </font>' + data['text'];
            } else if (i === 'accessible') {

            } else if (i === 'size') {
                data['text'] += ' <font style="color:#AF615B"> ( </font>' + '<font style="color:#AF615B">' + Data[i] + ' </font>' + ' <font style="color:#AF615B ">) </font> ';
            } else if (i === 'value') {
                data['text'] += ' ' + Data[i] + ' ';
            } else {
                if (data['text'] === '') {
                    data['text'] += '<font style="color:#00bcd4">' + Data[i] + '</font>';
                } else {
                    data['text'] += ' => ' + Data[i];
                }
            }
        } else {
            data['leaf'] = false;
            data['iconCls'] = 'icon-return';
            data['children'] = ReturnChildData(Data['children']);
        }
    }
    return new DataObj(data['text'], data['children'], data['leaf'], data['iconCls'] ? data['iconCls'] : 'icon-return-leaf');
}

let errorType = {
    '1': 'E_ERROR',
    '2': 'E_WARNING',
    '4': 'E_PARSE',
    '8': 'E_NOTICE',
    '16': 'E_CORE_WARNING',
    '32': 'E_CORE_WARNING',
    '64': 'E_COMPILE_ERROR',
    '128': 'E_COMPILE_WARNING',
    '256': 'E_USER_ERROR',
    '512': 'E_USER_WARNING',
    '1024': 'E_USER_NOTICE',
    '2048': 'E_STRICT',
    '4096': 'E_RECOVERABLE_ERROR',
    '16384': 'E_USER_DEPRECATED',
};


//错误数据处理
function ErrorObj(type, file, message, line, exception, backtrace) {
    let reg = /^[0-9]*$/;
    if (reg.test(type)) {
        type = errorType[type]; //错误类型解析
    }
    this.type = type;
    this.message = message;
    this.line = line;
    this.file = file;
    this.exception = exception;
    this.backtrace = backtrace;
}

// 将数据处理成errorTable面板可识别的数据格式
function ErrorTableObj(file = null, line = null, call = null) {
    this.call = call;
    this.file = file;
    this.line = line;
}