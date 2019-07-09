<html>
<head>
<style>
body {
	font-family:"futura-pt-condensed","trebuchet ms";
}
p {
	margin:0px;
}
li {
	margin-top:20px;
}
</style>
</head>
<body>
<form method="get">
<input type="input" name="q" value="<?php echo htmlentities($_GET['q'], ENT_QUOTES); ?>" />
<input type="submit" value="Search" />
</form>
<?php
set_time_limit(0);
define('INDEXLOCATION',dirname(__FILE__).'/index/');
define('DOCUMENTLOCATION',dirname(__FILE__).'/documents/');

include_once('./classes/indexer.class.php');
include_once('./classes/search.class.php');
include_once('./classes/multifolderindex.class.php');
include_once('./classes/multifolderdocumentstore.class.php');
include_once('./classes/ranker.class.php');

$index = new multifolderindex();
$docstore = new multifolderdocumentstore();
$ranker = new ranker();
$indexer = new indexer($index,$docstore,$ranker);
$search = new search($index,$docstore,$ranker);


echo '<ul style="list-style:none;">';

// The regex in the below just removes non ASCII characters since the whole thing
// isn't unicode aware.
foreach($search->dosearch($_GET['q']) as $result) {
	?>
	<li>
		<a href="<?php echo $result[0]; ?>"><?php echo preg_replace('/[^(\x20-\x7F)]*/','',$result[1]); ?></a> <small>Rank: <?php echo $result[3]; ?></small><br>
		<a style="color:#093; text-decoration:none;" href="<?php echo $result[0]; ?>"><?php echo $result[0]; ?></a>
		<p><?php echo preg_replace('/[^(\x20-\x7F)]*/','',$result[2]); ?></p>
	</li>
	<?php
}
echo '</ul>';
?>
</body>
</html>
