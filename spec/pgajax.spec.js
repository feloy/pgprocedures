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

/* TESTS */
describe("PgProc", function() {
    it("returns integer", function(done) {
	fetch('/pgajax/tests@test_returns_integer.php')
	    .then(statusOk)
	    .then(getJson)
	    .then(data => {		
		expect(data).toBe(42);
		done();
	    }).catch(error => console.log('Error '+error));	
    });

    it("returns integer as string", function(done) {
	fetch('/pgajax/tests@test_returns_integer_as_string.php')
	    .then(statusOk)
	    .then(getJson)
	    .then(data => {
		expect(data).toBe('42');
		done();
	    }).catch(error => console.log('Request failed', error));
    });

    it("returns date", function(done) {
	fetch('/pgajax/tests@test_returns_date.php')
	    .then(statusOk)
	    .then(getJson)
	    .then(data => {
		expect(data).toMatch(/^\d{2}\/\d{2}\/\d{4}$/);
		done();
	    }).catch(error => console.log('Request failed', error));
    });

    it("returns composite", function(done) {
	fetch('/pgajax/tests@test_returns_composite.php')
	    .then(statusOk)
	    .then(getJson)
	    .then(elt => {
		expect(elt).toEqual({a: 1, b: "hello"});
		done();
	    })
	    .catch(error => console.log('Request failed', error));
    });

    it("returns setof composite", function(done) {
	fetch('/pgajax/tests@test_returns_setof_composite.php')
	    .then(statusOk)
	    .then(getJson)
	    .then(function(data) {
		expect(data).toEqual([{a: 1, b: 'hello'}, {a: 2, b: 'bye'}]);
		done();
	    }).catch(function(error) {  
		console.log('Request failed', error);  
	    });
    });

    it("not found exception", function(done) {
	fetch('/pgajax/tests@not_found_function.php')
	    .then(statusOk)
	    .then(getJson)
	    .then(elt => console.log(elt))
	    .catch(error => {
		if (error instanceof PgProcFunctionNotAvailableError) {
		    done();
		} else {
		    console.log('Request failed: ', error)	    
		}
	    });
	});

    it("raised exception", function(done) {
	fetch('/pgajax/tests@function_raising_exception.php')
	    .then(statusOk)
	    .then(getJson)
	    .then(elt => console.log(elt))
	    .catch(error => {
		if (error instanceof PgProcError) {
		    done();
		} else {
		}
	    });
    });
});
