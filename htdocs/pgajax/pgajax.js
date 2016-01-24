/* Personalized errors */
function PgProcFunctionNotAvailableError(message) {
    this.message = message || "PgProc function not available";
}
PgProcFunctionNotAvailableError.prototype = Object.create(Error.prototype);
PgProcFunctionNotAvailableError.prototype.constructor = PgProcFunctionNotAvailableError;


function PgProcError(message) {
    this.message = message || "PgProc error";
}
PgProcError.prototype = Object.create(Error.prototype);
PgProcError.prototype.constructor = PgProcError;


function PgProc($schema, $procedure, $args) {

    /* Fetch utilities */
    function statusOk(response) {  
	if (response.status >= 200 && response.status < 300) {  
	    return Promise.resolve(response)  
	} else if (response.status == 404) {
	    return Promise.reject(new PgProcFunctionNotAvailableError(response.statusText))
	} else if (response.status == 400) {
	    return Promise.reject(new PgProcError(response.statusText))
	} else {  
	    return Promise.reject(new Error(response.statusText))  
	}  
    }
    
    function getJson(response) {  
	return response.json()  
    }
    
    return new Promise(
	function(resolve, reject) {
	    fetch("/pgajax/"+$schema+"@"+$procedure+".php", {
		method: 'post',
		body: JSON.stringify($args)
	    })
		.then(statusOk)
		.then(getJson)
		.then(response => resolve(response))
		.catch(error => reject(error));
	});
}
