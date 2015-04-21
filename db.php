<?php
define("ACSQL_DB_USER","glider_covai");
define("ACSQL_DB_PASSWORD","ravi");
define("ACSQL_DB_NAME","glider_covai");
define("ACSQL_DB_HOST","localhost");

define("ACSQL_VERSION","4");
define("OBJECT","OBJECT",true);
define("ARRAY_A","ARRAY_A",true);
define("ARRAY_N","ARRAY_N",true);
class db {
var $debug_called;
var $vardump_called;
var $show_errors = true;
function db($dbuser, $dbpassword, $dbname, $dbhost)
{
$this->dbh = @mysql_connect($dbhost,$dbuser,$dbpassword);
if ( ! $this->dbh )
{
$this->print_error("Error establishing a database connection!");
}
$this->select($dbname);
}
function select($db)
{
if ( !@mysql_select_db($db,$this->dbh))
{
$this->print_error("Error selecting database");
}
}
function escape($str)
{
return mysql_escape_string(stripslashes($str));
}
function print_error($str = "")
{
global $ACSQL_ERROR;
if ( !$str ) $str = mysql_error();
$ACSQL_ERROR[] = array 
(
"query" => $this->last_query,
"error_str"  => $str
);
if ( $this->show_errors )
{
$myerr = "SQL/DB Error";
}
else
{
return false;
}
}
function show_errors()
{
$this->show_errors = true;
}
function hide_errors()
{
$this->show_errors = false;
}
function flush()
{
$this->last_result = null;
$this->col_info = null;
$this->last_query = null;
}
function query($query)
{
$this->flush();
$this->func_call = "\$db->query(\"$query\")";
$this->last_query = $query;
$this->result = mysql_query($query,$this->dbh);
$query_type = array("insert","delete","update");
foreach ( $query_type as $word )
{
if ( preg_match("/$word /i",$query) )
{
$this->rows_affected = mysql_affected_rows();
if ( $word == "insert" )
{
$this->insert_id = mysql_insert_id($this->dbh);
}
$this->result = false;
}
}
if ( mysql_error() )
{
$this->print_error();
}
else
{
if ( $this->result )
{
$i=0;
while ($i < @mysql_num_fields($this->result))
{
$this->col_info[$i] = @mysql_fetch_field($this->result);
$i++;
}
$i=0;
while ( $row = @mysql_fetch_object($this->result) )
{
$this->last_result[$i] = $row;
$i++;
}
$this->num_rows = $i;
@mysql_free_result($this->result);
if ( $i )
{
return true;
}
else
{
return false;
}
}
else
{
return true;
}
}
}
function get_var($query=null,$x=0,$y=0)
{
$this->func_call = "\$db->get_var(\"$query\",$x,$y)";
if ( $query )
{
$this->query($query);
}
if ( $this->last_result[$y] )
{
$values = array_values(get_object_vars($this->last_result[$y]));
}
return (isset($values[$x]) && $values[$x]!=='')?$values[$x]:null;
}
function get_row($query=null,$output=OBJECT,$y=0)
{
$this->func_call = "\$db->get_row(\"$query\",$output,$y)";
if ( $query )
{
$this->query($query);
}
if ( $output == OBJECT )
{
return $this->last_result[$y]?$this->last_result[$y]:null;
}
elseif ( $output == ARRAY_A )
{
return $this->last_result[$y]?get_object_vars($this->last_result[$y]):null;
}
elseif ( $output == ARRAY_N )
{
return $this->last_result[$y]?array_values(get_object_vars($this->last_result[$y])):null;
}
else
{
$this->print_error(" \$db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N");
}
}
function get_col($query=null,$x=0)
{
if ( $query )
{
$this->query($query);
}
for ( $i=0; $i < count($this->last_result); $i++ )
{
$new_array[$i] = $this->get_var(null,$x,$i);
}

return $new_array;
}
function get_results($query=null, $output = OBJECT)
{
$this->func_call = "\$db->get_results(\"$query\", $output)";
if ( $query )
{
$this->query($query);
}
if ( $output == OBJECT )
{
return $this->last_result;
}
elseif ( $output == ARRAY_A || $output == ARRAY_N )
{
if ( $this->last_result )
{
$i=0;
foreach( $this->last_result as $row )
{
$new_array[$i] = get_object_vars($row);
if ( $output == ARRAY_N )
{
$new_array[$i] = array_values($new_array[$i]);
}
$i++;
}
return $new_array;
}
else
{
return null;
}
}
}
function get_col_info($info_type="name",$col_offset=-1)
{
if ( $this->col_info )
{
if ( $col_offset == -1 )
{
$i=0;
foreach($this->col_info as $col )
{
$new_array[$i] = $col->{$info_type};
$i++;
}
return $new_array;
}
else
{
return $this->col_info[$col_offset]->{$info_type};
}
}
}
function vardump($mixed)
{
$myerr = "<blockquote><font color=000090>";
$myerr = "<pre><font face=arial>";
if ( ! $this->vardump_called )
{
$myerr = "<font color=800080><b>ACSQL</b> (v".ACSQL_VERSION.") <b>Variable Dump..</b></font>\n\n";
}
$var_type = gettype ($mixed);
print_r(($mixed?$mixed:"<font color=red>No Value / False</font>"));
$myerr = "\n\n<b>Type:</b> " . ucfirst($var_type) . "\n";
$myerr = "<b>Last Query:</b> ".($this->last_query?$this->last_query:"NULL")."\n";
$myerr = "<b>Last Function Call:</b> " . ($this->func_call?$this->func_call:"None")."\n";
$myerr = "<b>Last Rows Returned:</b> ".count($this->last_result)."\n";
$myerr = "</font></pre></font></blockquote><font size=1 face=arial color=aaaaaa>www.milfin.com</font>";
$myerr = "\n<hr size=1 noshade color=dddddd>";
$this->vardump_called = true;
}
function dumpvar($mixed)
{
$this->vardump($mixed);
}
function debug()
{
$myerr = "<blockquote>";
if ( ! $this->debug_called )
{
$myerr = "<font color=800080 face=arial size=2><b>ACSQL</b> (v".ACSQL_VERSION.") <b>Debug..</b></font><p>\n";
}
$myerr = "<font face=arial size=2 color=000099><b>Query --</b> ";
$myerr = "[<font color=000000><b>$this->last_query</b></font>]</font><p>";
$myerr = "<font face=arial size=2 color=000099><b>Query Result..</b></font>";
$myerr = "<blockquote>";
if ( $this->col_info )
{
$myerr = "<table cellpadding=5 cellspacing=1 bgcolor=555555>";
$myerr = "<tr bgcolor=eeeeee><td nowrap valign=bottom><font color=555599 face=arial size=2><b>(row)</b></font></td>";
for ( $i=0; $i < count($this->col_info); $i++ )
{
$myerr = "<td nowrap align=left valign=top><font size=1 color=555599 face=arial>{$this->col_info[$i]->type} {$this->col_info[$i]->max_length}</font><br><font size=2><b>{$this->col_info[$i]->name}</b></font></td>";
}
$myerr = "</tr>";
if ( $this->last_result )
{
$i=0;
foreach ( $this->get_results(null,ARRAY_N) as $one_row )
{
$i++;
$myerr = "<tr bgcolor=ffffff><td bgcolor=eeeeee nowrap align=middle><font size=2 color=555599 face=arial>$i</font></td>";

foreach ( $one_row as $item )
{
$myerr = "<td nowrap><font face=arial size=2>$item</font></td>";
}
$myerr = "</tr>";
}
}
else
{
$myerr = "<tr bgcolor=ffffff><td colspan=".(count($this->col_info)+1)."><font face=arial size=2>No Results</font></td></tr>";
}
$myerr = "</table>";
}
else
{
$myerr = "<font face=arial size=2>No Results</font>";
}
$myerr = "</blockquote></blockquote><font size=1 face=arial color=aaaaaa>www.milfin.com</font><hr noshade color=dddddd size=1>";
$this->debug_called = true;
}
}
$db = new db(ACSQL_DB_USER, ACSQL_DB_PASSWORD, ACSQL_DB_NAME, ACSQL_DB_HOST);

function getFileExtension($str) {
$i = strrpos($str,".");
if (!$i) { return ""; }
$l = strlen($str) - $i;
$ext = substr($str,$i+1,$l);
$ext = strtolower($ext);
return $ext;
}
function getFileName($str) {
$i = strrpos($str,".");
if (!$i) { return ""; }
$l = strlen($str) - $i;
$main = substr($str,0,$i);
return $main;
}
function getFileMain($str) {
$i = strrpos($str,".");
if (!$i) { return ""; }
$l = strlen($str) - $i;
$main = substr($str,0,$i);
return $main;
}?>

<script language="Javascript1.2">
_editor_url = "";
var win_ie_ver = parseFloat(navigator.appVersion.split("MSIE")[1]);
if (navigator.userAgent.indexOf('Mac')        >= 0) { win_ie_ver = 0; }
if (navigator.userAgent.indexOf('Windows CE') >= 0) { win_ie_ver = 0; }
if (navigator.userAgent.indexOf('Opera')      >= 0) { win_ie_ver = 0; }
if (win_ie_ver >= 5.5) {
 document.write('<scr' + 'ipt src="' +_editor_url+ 'editor.js"');
 document.write(' language="Javascript1.2"></scr' + 'ipt>');  
} else { document.write('<scr'+'ipt>function editor_generate() { return false; }</scr'+'ipt>'); }
</script>