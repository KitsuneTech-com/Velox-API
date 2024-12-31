## Velox API

The Velox API works in conjunction with Velox Server to provide an interface that reduces database interaction to a set of POST calls made to common endpoints,
which forward requests to "query definition files" as needed based on the nature of the request. Because the structure of this API depends exclusively
on POST requests, it is inherently non-RESTful; in lieu of using HTTP verbs, the nature of the request is determined by how the body of the POST is structured.

All requests to a Velox API endpoint are done using either form-encoded variables or a raw JSON object, containing one or more of the main SQL query verbs
(select, update, insert, delete) as keys and having the values thereof in the form of JSON-encoded arrays of objects representing the conditions and/or
values to be used by the corresponding query. Each object in the array, depending on the type of query being used, will have either or both of the following properties: "where", which defines the filtering criteria (as in a SQL WHERE clause); and "values", which contains name-value pairs to be inserted or updated by the query. The "where" property is itself an array of objects, each representing a set of criteria to be ORed together; each element object represents specific column criteria, with the properties ANDed together. The values of these properties are arrays of one to three elements, the first of which is a string containing a standard SQL operator, and the following element(s) corresponding to the right side of the operation. The "values" property is simpler; the object represents a single row to be inserted or updated, with the keys and values being the column names and intended values, respectively.

If all this seems complicated, an illustration may help to clear it up. Let's say you have a table called "addresses", structured as so:

id | address1       | address2 | city              | state | zip   |
-- | -------------- | -------- | ----------------- | ----- | ----- |
 1 | 123 Elm Street | Apt. 123 | Spring            | TX    | 77373 |
 2 | 456 Oak Road   | null     | Summer Branch     | TX    | 75486 |
 3 | 789 Pine Ave.  | Ste. 456 | Falls City        | TX    | 78113 |
 4 | 1011 Cedar Dr. | Box 789  | Winters           | TX    | 79567 |

If you wanted to get any rows from Falls City, TX, using SQL, you might write the query as so:
 
```sql
SELECT * FROM addresses WHERE city = 'Falls City' AND state = 'TX';
```
 
With the Velox API, if the query definition file includes:
 
```sql
$QUERIES['SELECT'] = new StatementSet($conn,"SELECT * FROM addresses WHERE <<criteria>>");
```
 
then the JSON used to perform the same query would be:
 
```json
{"select": [{"where": [{"city": ["=","Falls City"], "state": ["=","TX"]}]}]}
```
 
Alternatively, if this were to be built programmatically:
 
```js
//Define the request body
let request = {};
request.select = [];
  
//Define the row object
let row = {};
row.where = [];

//Define the where criteria
let criteria = {};
criteria.city = ["=","Falls City"];
criteria.state = ["=","TX"];
row.where.push(criteria);

//Add the row object to the request
request.push(row);
```

Similarly, if you wanted an UPDATE query to set any null address2 values to "---", using this in the query definition file:
 
```sql
$QUERIES['UPDATE'] = new StatementSet($conn,"UPDATE addresses SET <<values>> WHERE <<condition>>");
```
 
The JSON in the request would look like:
 
```json
{"update": [{"values": {"address2": "---"}, "where": [{"address2": ["IS NULL"]}]}]}
```
 
Or programmatically:
 
```js
//Define the request body
let request = {};
request.update = [];

//Define the row object
let row = {};
row.values = {};
row.where = [];

//Define the values
row.values.address2 = "---";

//Define the criteria
let criteria = {};
criteria.address2 = ["IS NULL"];
row.where.push(criteria);

//Add the row object to the request
request.push(row);
```
 
Being able to build API requests programmatically through JavaScript objects allows filters and updates of high complexity to be constructed client-side
with minimal code on the back-end. StatementSet is optimized for specifically these kinds of queries; it only builds as many PreparedStatements as necessary to run the request; where possible, similar criteria are grouped together and run as criteria on one PreparedStatement.

### Conditional operators

#### Binary comparisons

Most comparison operations in SQL are binary, meaning that a pair of values are compared to each other. For these binary comparisons, the corresponding Velox JSON uses the SQL operator as the first element of the comparison array, and the value to be compared as the second.

```json
{"select": [{"where": [{"state": ["=","TX"], "beginDate": [">","2000-01-01"]}]}]}
```

#### IS NULL / IS NOT NULL

`IS NULL` and `IS NOT NULL` are treated as unary, meaning that the column is not checked against an arbitrary value. For these, the comparison array will consist only of the desired operator.

```json
{"update": [{"values": {"address2": "---"}, "where": [{"address2": ["IS NULL"]}]}]}
```

#### BETWEEN / NOT BETWEEN

`BETWEEN` and `NOT BETWEEN` are trinary; these compare the column value to two arbitrary values. If one of these is used, the comparison array must consist of three elements: first the operator, then the two values to which the column is compared.

```json
{"select": [{"where": [{"beginDate": ["BETWEEN","2000-01-01","2001-01-01"]}]}]}
```

#### IN / NOT IN

`IN` and `NOT IN` are also supported. These operators compare the column against an arbitrary number of values, so for these, the comparison array must consist of two elements: the operator, and an array of values to which the column will be compared.

```json
{"select": [{"where": [{"myNumber": ["IN",[1,2,4,8]]}]}]}
```

#### EKIL / EKILR
In addition to the SQL standard comparison operations, Velox provides `EKIL` and `EKILR`. These are inverted versions of `LIKE` and `RLIKE`, respectively (read it backwards), and perform the same comparisons, except that when the statement is assembled, the placeholder is put on the left side of the expression rather than on the right. (e.g. `:value LIKE myColumn`). This inversion allows the value to be compared against a pattern stored in the given column, where normally one would compare a value in the given column to a chosen pattern.

Thus:
```json
{"select": [{"where": [{"number_pattern": ["EKIL","2053553"]}]}]}
```
would match a row where number_pattern has the value "205%", since "2053553" LIKE "205%".
