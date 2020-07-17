Ext.define('phpray.model.PropertyAnalysisList', {
    extend: 'phpray.model.Base',

    fields: [
        'callee', 'caller', 'ct', 'wt', 'CPU', 'mu', 'pmu'
    ]
});