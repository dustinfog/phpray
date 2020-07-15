Ext.define('PhpRay.view.main.PropertyAnalysisList', {
    extend: 'Ext.grid.Panel',
    xtype: 'PropertyAnalysisList',

    requires: [
        'PhpRay.store.PropertyAnalysisList'
    ],

    store: {
        type: 'PropertyAnalysisList'
    },
    columns: [
        { text: '被调用者',  dataIndex: 'callee', width: '36%'},
        { text: '调用者', dataIndex: 'caller', width: '36%' },
        { text: '次数', dataIndex: 'ct', width: '5%' },
        { text: '时间', dataIndex: 'wt', width: '5%' },
        { text: 'CPU', dataIndex: 'CPU', width: '5%' },
        { text: '内存占用', dataIndex: 'mu', width: '6%' },
        { text: '内存峰值', dataIndex: 'pmu', width: '6%' },
    ],
});