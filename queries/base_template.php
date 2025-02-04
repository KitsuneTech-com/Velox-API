<?php


// Generic template for Velox query definition - replace placeholder info as appropriate
// -------------------------------------------------------------------------------------
// Query definitions created with this template should remain in the same directory to be accessible by the appropriate
// API endpoint.

// Note: these classes are defined by way of the autoloader include in the API endpoint script. As such, this template is only
// intended to be used in the context of the Velox API.
use KitsuneTech\Velox\Database\Connection;
use KitsuneTech\Velox\Database\Procedures\{Query, PreparedStatement, StatementSet};
use function KitsuneTech\Velox\Database\oneShot;

/*
$QUERY_VERSION should be incremented any time the result set schema changes. This will be sent as a header with the response
and allows the client-side service worker (if it exists) to update the schema of the corresponding IndexedDB instance. Per
the IndexedDB spec, version numbers must be positive integers. Normal semantic versioning doesn't apply here.
(If this variable doesn't exist or is set to 0, no IndexedDB will be generated by the service worker and the results
 will not be cached.)
*/

$QUERY_VERSION = 1;

//$conn should be either set with the appropriate values here, or better yet, should be assigned as a pre-existing connection.
$conn = $GLOBALS['VeloxConnections']['my-db-name'];
$conn = new Connection("server","database", "user", "password");

/*
$queries should be adjusted to correspond to the correct queries to populate and manipulate the data in the model. Additional
custom queries can be defined in this array and called by attaching the query name as a property to the request object, having
the appropriate 'where' and 'values' criteria as necessary.

Example: to call $QUERIES['myCustomQuery'], the request object would include something like 
  myCustomQuery: {where: [{field1: ["=","something"]}], values: {field1: "something else", field2: "another value"}

The query keys specified in this template ("SELECT","UPDATE","INSERT","DELETE") have specific meaning to the implementation
of this library. Specifically, $QUERIES['SELECT'] defines the data set, and the associated Query or PreparedStatement for
$QUERIES['UPDATE'], $QUERIES['INSERT'], or $QUERIES['DELETE'] should be written in such a way as to have the equivalent effect
on that data set. If any one of these query types is not intended to be used on the data set, it may be omitted. Each of the
latter three queries, when called, is followed by a new call to $QUERIES['SELECT'] to retrieve the updated result set. If
$QUERIES['SELECT'] is not defined, no Model is generated, and any results generated by a custom query are returned directly
as a JSON-formatted array of objects in name-value pair format.
*/

$QUERIES = [
    'SELECT' => new StatementSet($conn, "SELECT * FROM myTable WHERE <<condition>>"),
    'UPDATE' => new StatementSet($conn, "UPDATE myTable SET <<values>> WHERE <<condition>>"),
    'INSERT' => new StatementSet($conn, "INSERT INTO myTable (<<columns>>) VALUES (<<values>>)"),
    'DELETE' => new StatementSet($conn, "DELETE FROM myTable WHERE <<condition>>")
];

function preProcessing(&$vqlSelect, &$vqlUpdate, &$vqlInsert, &$vqlDelete){
    //If any pre-processing needs to be done before the Model is generated, do it here. The data for each query type is passed by
    //reference here, allowing it to be altered before it's used. This function can also be used to skip Model generation (as might
    //be preferred if a DML query is to be used standalone) by including an exit() or die() call. (Note that if this is to be done,
    //the relevant Velox procedure must be executed here; use the global keyword to bring it into the function scope.)
}

function postProcessing(&$model){
    //If any post-processing needs to be done after the Model is generated but before the results are output,
    //do it here. The Model instance for this query will be assigned to the $model argument, and this function
    //will be run after the instance is generated and synchronized. This function can be omitted if no post-processing
    //is needed.
}
