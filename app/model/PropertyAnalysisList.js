Ext.define('PhpRay.model.PropertyAnalysisList', {
    extend: 'PhpRay.model.Base',

    fields: [
        'callee', 'caller', 'ct', 'wt', 'CPU', 'mu', 'pmu'
    ]
});