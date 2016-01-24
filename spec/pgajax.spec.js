/* TESTS */
describe("PgProc", function() {
    it("returns integer", function(done) {
	PgProc('tests', 'test_returns_integer')
	    .then(data => {		
		expect(data).toBe(42);
		done();
	    }).catch(error => console.log('Error '+error));	
    });

    it("returns integer as string", function(done) {
	PgProc('tests', 'test_returns_integer_as_string')
	    .then(data => {
		expect(data).toBe('42');
		done();
	    }).catch(error => console.log('Request failed', error));
    });

    it("returns date", function(done) {
	PgProc('tests', 'test_returns_date')
	    .then(data => {
		expect(data).toMatch(/^\d{2}\/\d{2}\/\d{4}$/);
		done();
	    }).catch(error => console.log('Request failed', error));
    });

    it("returns composite", function(done) {
	PgProc('tests', 'test_returns_composite')
	    .then(elt => {
		expect(elt).toEqual({a: 1, b: "hello"});
		done();
	    })
	    .catch(error => console.log('Request failed', error));
    });

    it("returns setof composite", function(done) {
	PgProc('tests', 'test_returns_setof_composite')
	    .then(function(data) {
		expect(data).toEqual([{a: 1, b: 'hello'}, {a: 2, b: 'bye'}]);
		done();
	    }).catch(function(error) {  
		console.log('Request failed', error);  
	    });
    });

    it("not found exception", function(done) {
	PgProc('tests', 'not_found_function')
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
	PgProc('tests', 'function_raising_exception')
	    .then(elt => console.log(elt))
	    .catch(error => {
		if (error instanceof PgProcError) {
		    done();
		} else {
		}
	    });
    });

    it("incremented integer", function(done) {
	PgProc('tests', 'test_returns_incremented_integer', { 'n': 4 })
	    .then(val => {
		expect(val).toEqual(5)
		done();
	    })
	    .catch(error => console.log(error));		  
    });

    it("incremented numeric", function(done) {
	PgProc('tests', 'test_returns_incremented_numeric', { 'n': 4 })
	    .then(val => {
		expect(val).toEqual(5.5)
		done();
	    })
	    .catch(error => console.log(error));		  
    });

    it("incremented real", function(done) {
	PgProc('tests', 'test_returns_incremented_real', { 'n': 4 })
	    .then(val => {
		expect(val).toEqual(5.42)
		done();
	    })
	    .catch(error => console.log(error));		  
    });

    it("cat string", function(done) {
	PgProc('tests', 'test_returns_cat_string', { 's': 'hello' })
	    .then(val => {
		expect(val).toEqual('hello.')
		done();
	    })
	    .catch(error => console.log(error));		  
    });

    it("same bool true", function(done) {
	PgProc('tests', 'test_returns_same_bool', { 'b': true })
	    .then(val => {
		expect(val).toBe(true)
		done();
	    })
	    .catch(error => console.log(error));		  
    });

    it("same bool false", function(done) {
	PgProc('tests', 'test_returns_same_bool', { 'b': false })
	    .then(val => {
		expect(val).toBe(false)
		done();
	    })
	    .catch(error => console.log(error));		  
    });

    it("integer array as arg", function(done) {
	var list = [1, 2, 3, 4]
	PgProc('tests', 'test_integer_array_arg', { 'list': list })
	    .then(val => {
		expect(val).toEqual(list)
		done();
	    })
	    .catch(error => console.log(error));		  
    });

    it("varchar array as arg", function(done) {
	var list = ['a', 'b', 'c']
	PgProc('tests', 'test_varchar_array_arg', { 'list': list })
	    .then(val => {
		expect(val).toEqual(list)
		done();
	    })
	    .catch(error => console.log(error));		  
    });
});
