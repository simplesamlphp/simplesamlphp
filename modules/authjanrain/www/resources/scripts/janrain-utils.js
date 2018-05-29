function janrainUtilityFunctions() {
    function getCaptureFormItem(formName, fieldName) {
        return document.getElementById('capture_'+ formName +'_form_item_' + fieldName);
    }
    function getCaptureField(formName, fieldName) {
        return document.getElementById('capture_'+ formName +'_'+ fieldName);
    }
    function getParameterByName(name) {
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
        var results = regex.exec(location.search);
        return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
    }
    function showEvents() {
        function addEventHandler(e) {
            janrain.events[e].addHandler(function(result) {
                console.log(e, result);
            });
        }
        if (window.console && window.console.log) {
            for (var janrainEvent in janrain.events) {
                try {
                    var eventName = janrainEvent;

                    if(janrainEvent.hasOwnProperty('eventName')) {
                        eventName = janrainEvent.eventName;
                    }

                    addEventHandler(eventName);
                } catch(err) {
                    // No op.
                    // If we got here, the object it was working with was not an
                    // event and can safely be ignored.
                }
            }
        }
    }
    return {
        getCaptureFormItem: getCaptureFormItem,
        getCaptureField: getCaptureField,
        getParameterByName: getParameterByName,
        showEvents: showEvents
    };
}