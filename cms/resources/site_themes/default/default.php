<?php

$template_matrix = [
	['index',				'html'],
	['about',				'html'],
	['archives',			'html'],
	['categories',			'html'],
	['comments',			'html'],
	['comment_preview',		'html'],
	['site',				'css']
];

//-------------------------------------
//	Stylesheet template
//-------------------------------------

function site()
{
ob_start();
?>
body
{
	margin: 0 auto;
	padding: 0;
	color: #333;
	background: #585756 url("./themes/site_themes/default/bg.gif") repeat;
	font-size: 80%
}

h1, h2, h3 {
font-family: georgia, times new roman, times, serif;
letter-spacing: 0.09em;
}

h4 {
font-family: lucida grande, verdana, arial, helvetica, sans-serif;
margin-bottom: 4px;
}

p {
font-family: times new roman, times, serif;
}

ol {
	margin-bottom: 10px;
}

.center {
text-align: center;
}

blockquote {
font-family: trebuchet ms, verdana, arial, helvetica, sans-serif;
}

ul {
list-style: square;
margin-top: 3px;
margin-bottom: 3px;
margin-left: 1em;
padding-left: 1em;
}

img {
margin: 0;
padding: 0;
border: 0;
}

a:link { background-color: transparent; text-decoration: none; color: #663300; }
a:hover { background-color: #663300; text-decoration: none; color: #fff; }
a:visited { background-color: transparent; text-decoration: none; color: #663300; }

#topbar {
margin:0 auto;
padding:0;
height: 45px;
background: #FBFAF4;
border-top: 8px solid #232863;
border-bottom: 1px solid #333;
}

.secondbar {
margin:0 1px 0 0;
padding:0;
height: 1px;
background: #3C3B3A;
border-top: 1px solid #31302F;
border-bottom: 1px solid #50504E;
}

#wrapper {
margin: -57px auto 0 auto;
padding-bottom: 10px;
width: 740px;
border-top: 8px solid #232863;
background: #585756 url("./themes/site_themes/default/bg.gif") repeat;
color: #333;
}

#navbar {
margin:0 0 0 125px;
padding:3px 0 3px 0;
background: #FBFAF4;
font: 16px lucida grande, verdana, arial, helvetica, sans-serif;
text-align: center;
}

#navbar ul {
list-style: none;
}

#navbar li {
float: left;
padding: 0 23px 0 23px;
margin-right: 5px;
list-style: none;
}

#navbar li a {	display: block;
padding: 0.75em 0 0.25em;
text-transform: uppercase;
color: #000;}

#navbar a:hover {background: transparent;}

#header {
margin: 0 0 0 0;
padding: 0 10px 5px 20px;
border-bottom: 1px solid #ccc;
background: #FBFAF4;
border-left: 1px solid #333;
border-right: 1px solid #333;
border-bottom: 1px solid #333;
}

#blogtitle {
font-size: 1.25em;
color: #2F4C12;
float: left;
margin: 7px 0 0 0;
padding: 8px 4px 4px 4px;
width: 700px;
border-top: 1px solid #333;
}

#blogtitle h1 {
margin: 0;
padding-top: 5px;
font: 160% Georgia, Times, serif;
letter-spacing: 0.1em;
text-align: left;
}

#nav {
float: left;
margin: 0;
padding: 0;
width: 350px;
text-align: right;
background: transparent;
color: #333;
font-size: 70%;
font-variant: small-caps;
letter-spacing: 0.09em;
}

#content {
float: left;
margin: 15px 0 10px 0;
padding: 10px 10px 0 10px;
background: #FfFfFa;
border-left: 1px solid #000;
border-top: 1px solid #000;
border-right: 1px solid #000;
}

#blog {
float: left;
margin-right: 5px;
padding: 0 10px 10px 10px;
width: 440px;
color: #333;
text-align: left;
}

.entry {
margin-top: 10px;
padding: 0 10px 10px 10px;
border: 1px solid #ccc;
background: #F9F8F2;
color: #333;
}

#sidebar {
float: left;
margin-left: 5px;
padding: 10px 10px 10px 15px;
border: 1px solid #ccc;
width: 219px;
background: #F9F8F2;
color: #333;
text-align: left;
}

#footer {
margin: 0;
padding: 5px 10px;
border-top: 1px solid #ccc;
border-bottom: 1px solid #ccc;
background: #fff;
color: #333;
font-size: 70%;
letter-spacing: 0.09em;
}

.date {
font-size: 120%;
background: transparent;
color: #000;
}

.title {
font-size: 130%;
font-weight: normal;
background: transparent;
color: #336600;
border-bottom: 1px solid #ddd;
}

.posted {
margin-bottom: 10px;
font: 10px lucida grande, verdana, arial, helvetica, sans-serif;
background: transparent;
color: #666;
}

.sidetitle {
margin: 18px 0 7px 0;
font-size: 115%;
letter-spacing: 0.09em;
font-weight: normal;
background: transparent;
color: #666600;
border-bottom: 1px dotted #ccc;
}

.spacer {
clear: both;
}

.paginate {
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:			12px;
 font-weight: 		normal;
 letter-spacing:	.1em;
 padding:			10px 6px 10px 4px;
 margin:			0;
 background-color:	transparent;
}

.pagecount {
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:			10px;
 color:				#666;
 font-weight:		normal;
 background-color: transparent;
}

.calendarBG {
 background-color: #000;
}

.calendarBlank {
 background-color: #9DB7A7;
}

.calendarHeader {
 font-weight: bold;
 color: #fff;
 text-align: center;
 background-color: #000;
}

.calendarMonthLinks {
 font-family:       Arial, Trebuchet MS, Tahoma, Verdana, Sans-serif;
 font-size:         11px;
 font-weight:		bold;
 letter-spacing:	.1em;
 text-decoration:   none;
 color:             #fff;
 background-color:  transparent;
}

.calendarMonthLinks a {
 color:             #fff;
 text-decoration:   none;
 background-color:  transparent;
}

.calendarMonthLinks a:visited {
 color:             #fff;
 text-decoration:   none;
 background-color:  transparent;
}

.calendarMonthLinks a:hover {
 color:             #ccc;
 text-decoration:   underline;
 background-color:  transparent;
}

.calendarDayHeading {
 font-weight: bold;
 font-size:	11px;
 color: #fff;
 background-color: #195337;
 text-align:  center;
 vertical-align: middle;
}

.calendarToday {
 font-family:       Arial, Trebuchet MS, Tahoma, Verdana, Sans-serif;
 font-size:         12px;
 font-weight:		bold;
 letter-spacing:	.1em;
 text-decoration:   none;
 text-align:  center;
 vertical-align: middle;
 color:             #000;
 background-color: 	#ccc;
}

.calendarCell {
 font-family:       Arial, Trebuchet MS, Tahoma, Verdana, Sans-serif;
 font-size:         12px;
 font-weight:		bold;
 letter-spacing:	.1em;
 text-decoration:   none;
 text-align:  center;
 vertical-align: middle;
 color:             #666;
 background-color:  #fff;
}

.calendarCell a {
 color:             #000;
 text-decoration:   underline;
 background-color:  transparent;
}

.calendarCell a:visited {
 color:             #000;
 text-decoration:   underline;
 background-color:  transparent;
}

.calendarCell a:hover {
 color:             #fff;
 text-decoration:   none;
 background-color:  transparent;
}

.input {
border-top:        1px solid #999999;
border-left:       1px solid #999999;
background-color:  #fff;
color:             #000;
font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
font-size:         11px;
height:            1.6em;
padding:           .3em 0 0 2px;
margin-top:        6px;
margin-bottom:     3px;
}

.textarea {
border-top:        1px solid #999999;
border-left:       1px solid #999999;
background-color:  #fff;
color:             #000;
font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
font-size:         11px;
margin-top:        3px;
margin-bottom:     3px;
}

.checkbox {
background-color:  transparent;
margin:            3px;
padding:           0;
border:            0;
}

.submit {
background-color:  #fff;
font-family:       Arial, Verdana, Sans-serif;
font-size:         11px;
font-weight:       normal;
letter-spacing:    .1em;
padding:           1px 3px 1px 3px;
margin-top:        6px;
margin-bottom:     4px;
text-transform:    uppercase;
color:             #000;
}
<?php

$buffer = ob_get_contents();
ob_end_clean();
return $buffer;
}
/* END */



//-------------------------------------
//	Index template
//-------------------------------------

function index()
{
ob_start();
?>

{assign_variable:my_weblog="default_site"}
{assign_variable:my_template_group="site"}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>{exp:weblogs:info weblog="{my_weblog}"}{blog_title}{/exp:weblogs:info}</title>
<meta http-equiv="Content-Type" content="text/html; charset={charset}" />

<link rel='stylesheet' type='text/css' media='all' href='{stylesheet={my_template_group}/site_css}' />
<style type='text/css' media='screen'>@import "{stylesheet={my_template_group}/site_css}";</style>

<link rel="alternate" type="application/rss+xml" title="RSS" href="{path={my_template_group}/rss}" />
<link rel="alternate" type="application/atom+xml" title="Atom" href="{path={my_template_group}/atom}" />

</head>

<body>

<div id="topbar"></div>
<div class="secondbar"></div>


<div id="wrapper">
<div id="header">

<ul id="navbar">
  <li id="home"><a href="{homepage}" title="Home">Home</a></li>
  <li id="about"><a href="{path={my_template_group}/about}" title="About">About</a></li>
  <li id="archives"><a href="{path={my_template_group}/archives}" title="Archives">Archives</a></li>
  <li id="contact">{encode="{notification_sender_email}" title="Contact"}</li>
</ul>

<div id="blogtitle"><h1>{exp:weblogs:info weblog="{my_weblog}"}{blog_title}{/exp:weblogs:info}</h1></div>
<div class="spacer"></div>
</div>
<div class="secondbar"></div>

<div class="spacer"></div>

<div id="content">


<div id="blog">

{exp:weblogs:category_heading weblog="{my_weblog}"}
<h2>{category_name}</h2>
{if category_description}
<p>{category_description}</p>
{/if}
{/exp:weblogs:category_heading}


{exp:weblogs:entries weblog="{my_weblog}" orderby="date" sort="desc" limit="15" disable="member_data"}

<div class="entry">

{date_heading}
<h3 class="date">{entry_date format='l, F d, Y'}</h3>
{/date_heading}

<h2 class="title">{title}</h2>

{body}

{extended}

<div class="posted">Posted by <a href="{profile_path=members/index}">{author}</a> on {entry_date format='m/d'} at {entry_date format='h:i A'}

<br />

{categories}
<a href="{path=site_index}">{category_name}</a> &#8226;
{/categories}

{if allow_comments}
({comment_total}) <a href="{url_title_path="{my_template_group}/comments"}">Comments</a> &#8226;
{/if}

<a href="{title_permalink={my_template_group}/index}">Permalink</a>

</div>

{paginate}

<div class="paginate">

<span class="pagecount">Page {current_page} of {total_pages} pages</span>  {pagination_links}

</div>

{/paginate}

</div>

{/exp:weblogs:entries}

</div>

<div id="sidebar">

<h2 class="sidetitle">About</h2>
<p>Quote meon an estimate et non interruptus stadium. Sic tempus fugit esperanto hiccup estrogen. Glorious baklava ex librus hup hey ad infinitum. Non sequitur condominium facile et geranium incognito.</p>


{exp:weblogs:calendar switch="calendarToday|calendarCell"}

<table class="calendarBG" border="0" cellpadding="5" cellspacing="1" summary="My Calendar">
<tr>
<th class="calendarHeader"><div class="calendarMonthLinks"><a href="{previous_path={my_template_group}/index}">&lt;&lt;</a></div></th>
<th class="calendarHeader" colspan="5">{date format="F Y"}</th>
<th class="calendarHeader"><div class="calendarMonthLinks"><a class="calendarMonthLinks" href="{next_path={my_template_group}/index}">&gt;&gt;</a></div></th>
</tr>
<tr>
{calendar_heading}
<td class="calendarDayHeading">{lang:weekday_abrev}</td>
{/calendar_heading}
</tr>
{calendar_rows }
{row_start}<tr>{/row_start}
{if entries}<td class='{switch}' align='center'><a href="{day_path={my_template_group}/index}">{day_number}</a></td>{/if}
{if not_entries}<td class='{switch}' align='center'>{day_number}</td>{/if}
{if blank}<td class='calendarBlank'>&nbsp;</td>{/if}
{row_end}</tr>{/row_end}
{/calendar_rows}
</table>
{/exp:weblogs:calendar}


{exp:search:simple_form search_in="everywhere"}
<h2 class="sidetitle">Search</h2>
<p>
<input type="text" name="keywords" value="" class="input" size="18" maxlength="100" />
<br />
<a href="{path=search/index}">Advanced Search</a>
</p>

<p><input type="submit" value="submit"  class="submit" /></p>

{/exp:search:simple_form}


<h2 class="sidetitle">Categories</h2>
<p>
{exp:weblogs:categories weblog="{my_weblog}" style="nested"}
<a href="{path={my_template_group}/index}">{category_name}</a>
{/exp:weblogs:categories}
</p>

<h2 class="sidetitle">Monthly Archives</h2>
<ul>
{exp:weblogs:month_links weblog="{my_weblog}"}
<li><a href="{path={my_template_group}/index}">{month} {year}</a></li>
{/exp:weblogs:month_links}
<li><a href="{path={my_template_group}/archives}">Complete Archives</a></li>
<li><a href="{path={my_template_group}/categories}">Category Archives</a></li>
</ul>


<h2 class="sidetitle">Most recent entries</h2>
<ul>
{exp:weblogs:entries orderby="date" sort="desc" limit="15" weblog="{my_weblog}" dynamic="off" disable="pagination|custom_fields|categories|member_data"}
<li><a href="{title_permalink={my_template_group}/index}">{title}</a></li>
{/exp:weblogs:entries}
</ul>


<h2 class="sidetitle">Syndicate</h2>
<ul>
<li><a href="{path={my_template_group}/atom}">Atom</a></li>
<li><a href="{path={my_template_group}/rss}">RSS 2.0</a></li>

</ul>

</div>
</div>
<br class="spacer" />
<div id="footer">

<div class="entry">
<h2 class="sidetitle">Site Statistics</h2>

<p>
Page rendered in {elapsed_time} seconds<br />

{exp:stats}
Total Entries: {total_entries}<br />
Total Comments: {total_comments}<br />
Most Recent Entry: {last_entry_date format="m/d/Y h:i a"}<br />
Most Recent Comment on:  {last_comment_date format="m/d/Y h:i a"}<br />
Total Members: {total_members}<br />
{/exp:stats}
</p>

</div>
<p><br /><a href="http://expressionengine.com/">Powered by ExpressionEngine</a></p>

</div>
</div>
</body>
</html>
<?php

$buffer = ob_get_contents();
ob_end_clean();
return $buffer;
}
/* END */




//-------------------------------------
//	About template
//-------------------------------------

function about()
{
ob_start();
?>
{assign_variable:my_weblog="default_site"}
{assign_variable:my_template_group="site"}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>{exp:weblogs:info weblog="{my_weblog}"}{blog_title}{/exp:weblogs:info}</title>
<meta http-equiv="Content-Type" content="text/html; charset={charset}" />

<link rel='stylesheet' type='text/css' media='all' href='{stylesheet={my_template_group}/site_css}' />
<style type='text/css' media='screen'>@import "{stylesheet={my_template_group}/site_css}";</style>

<link rel="alternate" type="application/rss+xml" title="RSS" href="{path={my_template_group}/rss}" />
<link rel="alternate" type="application/atom+xml" title="Atom" href="{path={my_template_group}/atom}" />

</head>

<body>

<div id="topbar"></div>
<div class="secondbar"></div>


<div id="wrapper">
<div id="header">

<ul id="navbar">
  <li id="home"><a href="{homepage}" title="Home">Home</a></li>
  <li id="about"><a href="{path={my_template_group}/about}" title="About">About</a></li>
  <li id="archives"><a href="{path={my_template_group}/archives}" title="Archives">Archives</a></li>
  <li id="contact">{encode="{notification_sender_email}" title="Contact"}</li>
</ul>

<div id="blogtitle"><h1>{exp:weblogs:info weblog="{my_weblog}"}{blog_title}{/exp:weblogs:info}</h1></div>
<div class="spacer"></div>
</div>
<div class="secondbar"></div>

<div class="spacer"></div>

<div id="content">


<div id="blog">
<div class="entry">

<h2 class="title">About</h2>
<p>Quote meon an estimate et non interruptus stadium. Sic tempus fugit esperanto hiccup estrogen. Glorious baklava ex librus hup hey ad infinitum. Non sequitur condominium facile et geranium incognito.</p>


</div>

<p><a href="{homepage}">&lt;&lt; Back to main</a></p>

</div>


<div id="sidebar">

<h2 class="sidetitle">Monthly Archives</h2>
<ul>
{exp:weblogs:month_links weblog="{my_weblog}"}
<li><a href="{path={my_template_group}/index}">{month} {year}</a></li>
{/exp:weblogs:month_links}

<li><a href="{path={my_template_group}/archives}">Complete Archives</a></li>
<li><a href="{path={my_template_group}/categories}">Category Archives</a></li>
</ul>


<h2 class="sidetitle">Most recent entries</h2>
<ul>
{exp:weblogs:entries orderby="date" sort="desc" limit="15" weblog="{my_weblog}" dynamic="off" disable="pagination|custom_fields|categories|member_data"}
<li><a href="{title_permalink={my_template_group}/index}">{title}</a></li>
{/exp:weblogs:entries}
</ul>


<h2 class="sidetitle">Syndicate</h2>
<ul>
<li><a href="{path={my_template_group}/atom}">Atom</a></li>
<li><a href="{path={my_template_group}/rss}">RSS 2.0</a></li>

</ul>

</div>
</div>
<br class="spacer" />
<div id="footer">

Page rendered in {elapsed_time} seconds &#8226;

<p><br /><a href="http://expressionengine.com/">Powered by ExpressionEngine</a></p>

</div>
</div>
</body>
</html>
<?php

$buffer = ob_get_contents();
ob_end_clean();
return $buffer;
}
/* END */


//-------------------------------------
//	Archives template
//-------------------------------------

function archives()
{
ob_start();
?>
{assign_variable:my_weblog="default_site"}
{assign_variable:my_template_group="site"}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>{exp:weblogs:info weblog="{my_weblog}"}{blog_title}{/exp:weblogs:info}</title>
<meta http-equiv="Content-Type" content="text/html; charset={charset}" />

<link rel='stylesheet' type='text/css' media='all' href='{stylesheet={my_template_group}/site_css}' />
<style type='text/css' media='screen'>@import "{stylesheet={my_template_group}/site_css}";</style>

<link rel="alternate" type="application/rss+xml" title="RSS" href="{path={my_template_group}/rss}" />
<link rel="alternate" type="application/atom+xml" title="Atom" href="{path={my_template_group}/atom}" />

</head>

<body>

<div id="topbar"></div>
<div class="secondbar"></div>


<div id="wrapper">
<div id="header">

<ul id="navbar">
  <li id="home"><a href="{homepage}" title="Home">Home</a></li>
  <li id="about"><a href="{path={my_template_group}/about}" title="About">About</a></li>
  <li id="archives"><a href="{path={my_template_group}/archives}" title="Archives">Archives</a></li>
  <li id="contact">{encode="{notification_sender_email}" title="Contact"}</li>
</ul>

<div id="blogtitle"><h1>{exp:weblogs:info weblog="{my_weblog}"}{blog_title}{/exp:weblogs:info}</h1></div>
<div class="spacer"></div>
</div>
<div class="secondbar"></div>

<div class="spacer"></div>

<div id="content">


<div id="blog">
<div class="entry">
{exp:weblogs:entries orderby="date" sort="desc" limit="100" disable="pagination|custom_fields|categories|member_data"}

{date_heading display="yearly"}
<h2 class="title">{entry_date format="Y"}</h2>
{/date_heading}

{date_heading display="monthly"}
<h3 class="date">{entry_date format="F"}</h3>
{/date_heading}

<ul>
<li><a href="{title_permalink="{my_template_group}/index"}">{title}</a></li>
</ul>

{/exp:weblogs:entries}
</div>

<p><a href="{homepage}">&lt;&lt; Back to main</a></p>

</div>


<div id="sidebar">

<h2 class="sidetitle">About</h2>
<p>Quote meon an estimate et non interruptus stadium. Sic tempus fugit esperanto hiccup estrogen. Glorious baklava ex librus hup hey ad infinitum. Non sequitur condominium facile et geranium incognito.</p>


<h2 class="sidetitle">Monthly Archives</h2>
<ul>
{exp:weblogs:month_links weblog="{my_weblog}"}
<li><a href="{path={my_template_group}/index}">{month} {year}</a></li>
{/exp:weblogs:month_links}

<li><a href="{path={my_template_group}/archives}">Complete Archives</a></li>
<li><a href="{path={my_template_group}/categories}">Category Archives</a></li>
</ul>


<h2 class="sidetitle">Most recent entries</h2>
<ul>
{exp:weblogs:entries orderby="date" sort="desc" limit="15" weblog="{my_weblog}" dynamic="off" disable="pagination|custom_fields|categories|member_data"}
<li><a href="{title_permalink={my_template_group}/index}">{title}</a></li>
{/exp:weblogs:entries}
</ul>


<h2 class="sidetitle">Syndicate</h2>
<ul>
<li><a href="{path={my_template_group}/atom}">Atom</a></li>
<li><a href="{path={my_template_group}/rss}">RSS 2.0</a></li>

</ul>

</div>
</div>
<br class="spacer" />
<div id="footer">

Page rendered in {elapsed_time} seconds &#8226;

<p><br /><a href="http://expressionengine.com/">Powered by ExpressionEngine</a></p>

</div>
</div>
</body>
</html>
<?php

$buffer = ob_get_contents();
ob_end_clean();
return $buffer;
}
/* END */




//-------------------------------------
//	Category archives template
//-------------------------------------

function categories()
{
ob_start();
?>
{assign_variable:my_weblog="default_site"}
{assign_variable:my_template_group="site"}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>{exp:weblogs:info weblog="{my_weblog}"}{blog_title}{/exp:weblogs:info}</title>
<meta http-equiv="Content-Type" content="text/html; charset={charset}" />

<link rel='stylesheet' type='text/css' media='all' href='{stylesheet={my_template_group}/site_css}' />
<style type='text/css' media='screen'>@import "{stylesheet={my_template_group}/site_css}";</style>

<link rel="alternate" type="application/rss+xml" title="RSS" href="{path={my_template_group}/rss}" />
<link rel="alternate" type="application/atom+xml" title="Atom" href="{path={my_template_group}/atom}" />

</head>

<body>

<div id="topbar"></div>
<div class="secondbar"></div>


<div id="wrapper">
<div id="header">

<ul id="navbar">
  <li id="home"><a href="{homepage}" title="Home">Home</a></li>
  <li id="about"><a href="{path={my_template_group}/about}" title="About">About</a></li>
  <li id="archives"><a href="{path={my_template_group}/archives}" title="Archives">Archives</a></li>
  <li id="contact">{encode="{notification_sender_email}" title="Contact"}</li>
</ul>

<div id="blogtitle"><h1>{exp:weblogs:info weblog="{my_weblog}"}{blog_title}{/exp:weblogs:info}</h1></div>
<div class="spacer"></div>
</div>
<div class="secondbar"></div>

<div class="spacer"></div>

<div id="content">


<div id="blog">

<h2 class="sidetitle">Categories</h2>
<div class="entry">
{exp:weblogs:category_archive weblog="{my_weblog}"}

{categories}<h4>{category_name}</h4>{/categories}

{entry_titles}<a href="{path={my_template_group}/comments}">{title}</a>{/entry_titles}

{/exp:weblogs:category_archive}
</div>
<p><a href="{homepage}">&lt;&lt; Back to main</a></p>

</div>


<div id="sidebar">

<h2 class="sidetitle">About</h2>
<p>Quote meon an estimate et non interruptus stadium. Sic tempus fugit esperanto hiccup estrogen. Glorious baklava ex librus hup hey ad infinitum. Non sequitur condominium facile et geranium incognito.</p>


<h2 class="sidetitle">Monthly Archives</h2>
<ul>
{exp:weblogs:month_links weblog="{my_weblog}"}
<li><a href="{path={my_template_group}/index}">{month} {year}</a></li>
{/exp:weblogs:month_links}

<li><a href="{path={my_template_group}/archives}">Complete Archives</a></li>
<li><a href="{path={my_template_group}/categories}">Category Archives</a></li>
</ul>


<h2 class="sidetitle">Most recent entries</h2>
<ul>
{exp:weblogs:entries orderby="date" sort="desc" limit="15" weblog="{my_weblog}" dynamic="off" disable="pagination|custom_fields|categories|member_data"}
<li><a href="{title_permalink={my_template_group}/index}">{title}</a></li>
{/exp:weblogs:entries}
</ul>


<h2 class="sidetitle">Syndicate</h2>
<ul>
<li><a href="{path={my_template_group}/atom}">Atom</a></li>
<li><a href="{path={my_template_group}/rss}">RSS 2.0</a></li>

</ul>

</div>
</div>
<br class="spacer" />
<div id="footer">

Page rendered in {elapsed_time} seconds &#8226;

<p><br /><a href="http://expressionengine.com/">Powered by ExpressionEngine</a></p>

</div>
</div>
</body>
</html>

<?php

$buffer = ob_get_contents();
ob_end_clean();
return $buffer;
}
/* END */




//-------------------------------------
//	Comments
//-------------------------------------

function comments()
{
ob_start();
?>

{assign_variable:my_weblog="default_site"}
{assign_variable:my_template_group="site"}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>{exp:weblogs:info weblog="{my_weblog}"}{blog_title}{/exp:weblogs:info}</title>
<meta http-equiv="Content-Type" content="text/html; charset={charset}" />

<link rel='stylesheet' type='text/css' media='all' href='{stylesheet={my_template_group}/site_css}' />
<style type='text/css' media='screen'>@import "{stylesheet={my_template_group}/site_css}";</style>

<link rel="alternate" type="application/rss+xml" title="RSS" href="{path={my_template_group}/rss}" />
<link rel="alternate" type="application/atom+xml" title="Atom" href="{path={my_template_group}/atom}" />

</head>

<body>

<div id="topbar"></div>
<div class="secondbar"></div>


<div id="wrapper">
<div id="header">

<ul id="navbar">
  <li id="home"><a href="{homepage}" title="Home">Home</a></li>
  <li id="about"><a href="{path={my_template_group}/about}" title="About">About</a></li>
  <li id="archives"><a href="{path={my_template_group}/archives}" title="Archives">Archives</a></li>
  <li id="contact">{encode="{notification_sender_email}" title="Contact"}</li>
</ul>

<div id="blogtitle"><h1>{exp:weblogs:info weblog="{my_weblog}"}{blog_title}{/exp:weblogs:info}</h1></div>
<div class="spacer"></div>
</div>
<div class="secondbar"></div>

<div class="spacer"></div>

<div id="content">

<div id="blog">

{exp:weblogs:entries limit="1" disable="member_data"}
<div class="entry">
<h2 class="title">{title}</h2>

{body}

<div class="posted">
Posted by {url_or_email_as_author} on {entry_date format='m/d'} at {entry_date format='h:i A'}
</div>
</div>
{/exp:weblogs:entries}

{exp:comments:entries weblog="{my_weblog}" limit="25"}
<div class="entry">
{comment}

<div class="posted">Posted by {url_or_email_as_author}  &nbsp;on&nbsp; {comment_date format='m/d'} &nbsp;at&nbsp; {comment_date format='h:i A'}</div>

{paginate}
<div class="paginate">
<span class="pagecount">Page {current_page} of {total_pages} pages</span>  {pagination_links}
</div>
{/paginate}

</div>

{/exp:comments:entries}


<div class="entry">
{exp:comments:form preview="{my_template_group}/comment_preview"}

{if logged_out}
<p>
Name:<br />
<input type="text" name="name" value="{name}" size="50" />
</p>
<p>
Email:<br />
<input type="text" name="email" value="{email}" size="50" />
</p>
<p>
Location:<br />
<input type="text" name="location" value="{location}" size="50" />
</p>
<p>
URL:<br />
<input type="text" name="url" value="{url}" size="50" />
</p>

{/if}

<p>
<textarea name="comment" cols="50" rows="12">{comment}</textarea>
</p>

{if logged_out}
<p><input type="checkbox" name="save_info" value="yes" {save_info} /> Remember my personal information</p>
{/if}

<p><input type="checkbox" name="notify_me" value="yes" {notify_me} /> Notify me of follow-up comments?</p>

<input type="submit" name="submit" value="Submit" />
<input type="submit" name="preview" value="Preview" />

{/exp:comments:form}
</div>

<div class="center">

{exp:weblogs:next_entry weblog="{my_weblog}"}
<p>Next entry: <a href="{path={my_template_group}/comments}">{title}</a></p>
{/exp:weblogs:next_entry}

{exp:weblogs:prev_entry weblog="{my_weblog}"}
<p>Previous entry: <a href="{path={my_template_group}/comments}">{title}</a></p>
{/exp:weblogs:prev_entry}

</div>


<p><a href="{homepage}">&lt;&lt; Back to main</a></p>


</div>
<div id="sidebar">

<h2 class="sidetitle">About</h2>
<p>Quote meon an estimate et non interruptus stadium. Sic tempus fugit esperanto hiccup estrogen. Glorious baklava ex librus hup hey ad infinitum. Non sequitur condominium facile et geranium incognito.</p>


<h2 class="sidetitle">Monthly Archives</h2>
<ul>
{exp:weblogs:month_links weblog="{my_weblog}"}
<li><a href="{path={my_template_group}/index}">{month} {year}</a></li>
{/exp:weblogs:month_links}
<li><a href="{path={my_template_group}/archives}">Complete Archives</a></li>
<li><a href="{path={my_template_group}/categories}">Category Archives</a></li>
</ul>


<h2 class="sidetitle">Most recent entries</h2>
<ul>
{exp:weblogs:entries orderby="date" sort="desc" limit="15" weblog="{my_weblog}" dynamic="off" disable="pagination|custom_fields|categories|member_data"}
<li><a href="{title_permalink={my_template_group}/index}">{title}</a></li>
{/exp:weblogs:entries}
</ul>


<h2 class="sidetitle">Syndicate</h2>
<ul>
<li><a href="{path={my_template_group}/atom}">Atom</a></li>
<li><a href="{path={my_template_group}/rss}">RSS 2.0</a></li>

</ul>

</div>
</div>

<br class="spacer" />
<div id="footer">

Page rendered in {elapsed_time} seconds &#8226;

<p><br /><a href="http://expressionengine.com/">Powered by ExpressionEngine</a></p>

</div>
</div>
</body>
</html>

<?php

$buffer = ob_get_contents();
ob_end_clean();
return $buffer;
}
/* END */




//-------------------------------------
//	Comment preview
//-------------------------------------

function comment_preview()
{
ob_start();
?>
{assign_variable:my_weblog="default_site"}
{assign_variable:my_template_group="site"}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>{exp:weblogs:info weblog="{my_weblog}"}{blog_title}{/exp:weblogs:info}</title>
<meta http-equiv="Content-Type" content="text/html; charset={charset}" />

<link rel='stylesheet' type='text/css' media='all' href='{stylesheet={my_template_group}/site_css}' />
<style type='text/css' media='screen'>@import "{stylesheet={my_template_group}/site_css}";</style>

<link rel="alternate" type="application/rss+xml" title="RSS" href="{path={my_template_group}/rss}" />
<link rel="alternate" type="application/atom+xml" title="Atom" href="{path={my_template_group}/atom}" />

</head>

<body>

<div id="topbar"></div>
<div class="secondbar"></div>


<div id="wrapper">
<div id="header">

<ul id="navbar">
  <li id="home"><a href="{homepage}" title="Home">Home</a></li>
  <li id="about"><a href="{path={my_template_group}/about}" title="About">About</a></li>
  <li id="archives"><a href="{path={my_template_group}/archives}" title="Archives">Archives</a></li>
  <li id="contact">{encode="{notification_sender_email}" title="Contact"}</li>
</ul>

<div id="blogtitle"><h1>{exp:weblogs:info weblog="{my_weblog}"}{blog_title}{/exp:weblogs:info}</h1></div>
<div class="spacer"></div>
</div>
<div class="secondbar"></div>

<div class="spacer"></div>

<div id="content">

<div id="blog">

<div class="entry">
{exp:comments:preview}
{comment}
{/exp:comments:preview}
</div>

<div class="entry">
{exp:comments:form}

{if logged_out}
<p>
Name:<br />
<input type="text" name="name" value="{name}" size="50" />
</p>
<p>
Email:<br />
<input type="text" name="email" value="{email}" size="50" />
</p>
<p>
Location:<br />
<input type="text" name="location" value="{location}" size="50" />
</p>
<p>
URL:<br />
<input type="text" name="url" value="{url}" size="50" />
</p>

{/if}

<p>
<textarea name="comment" cols="50" rows="12">{comment}</textarea>
</p>

{if logged_out}
<p><input type="checkbox" name="save_info" value="yes" {save_info} /> Remember my personal information</p>
{/if}

<p><input type="checkbox" name="notify_me" value="yes" {notify_me} /> Notify me of follow-up comments?</p>


<input type="submit" name="submit" value="Submit" />
<input type="submit" name="preview" value="Preview" />

{/exp:comments:form}
</div>

<p><a href="{homepage}">&lt;&lt; Back to main</a></p>
</div>


<div id="sidebar">

<h2 class="sidetitle">About</h2>
<p>Quote meon an estimate et non interruptus stadium. Sic tempus fugit esperanto hiccup estrogen. Glorious baklava ex librus hup hey ad infinitum. Non sequitur condominium facile et geranium incognito.</p>

<h2 class="sidetitle">Monthly Archives</h2>
<ul>
{exp:weblogs:month_links weblog="{my_weblog}"}
<li><a href="{path={my_template_group}/index}">{month} {year}</a></li>
{/exp:weblogs:month_links}

<li><a href="{path={my_template_group}/archives}">Complete Archives</a></li>
<li><a href="{path={my_template_group}/categories}">Category Archives</a></li>
</ul>


<h2 class="sidetitle">Most recent entries</h2>
<ul>
{exp:weblogs:entries orderby="date" sort="desc" limit="15" weblog="{my_weblog}" dynamic="off" disable="pagination|custom_fields|categories|member_data"}
<li><a href="{title_permalink={my_template_group}/index}">{title}</a></li>
{/exp:weblogs:entries}
</ul>


<h2 class="sidetitle">Syndicate</h2>
<ul>
<li><a href="{path={my_template_group}/atom}">Atom</a></li>
<li><a href="{path={my_template_group}/rss}">RSS 2.0</a></li>

</ul>

</div>
</div>
<br class="spacer" />
<div id="footer">

Page rendered in {elapsed_time} seconds &#8226;

<p><br /><a href="http://expressionengine.com/">Powered by ExpressionEngine</a></p>

</div>
</div>
</body>
</html>
<?php

$buffer = ob_get_contents();
ob_end_clean();
return $buffer;
}
/* END */



