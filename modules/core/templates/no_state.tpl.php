<?php

$this->data['header'] = $this->t('{core:no_state:header}');
$this->includeAtTemplateBase('includes/header.php');

echo('<h2>' . $this->t('{core:no_state:header}') . '</h2>');
echo('<p>' . $this->t('{core:no_state:description}') . '</p>');

echo('<h3>' . $this->t('{core:no_state:suggestions}') . '</h3>');
echo('<ul>');
echo('<li>' . $this->t('{core:no_state:suggestion_goback}') . '</li>');
echo('<li>' . $this->t('{core:no_state:suggestion_closebrowser}') . '</li>');
echo('</ul>');

echo('<h3>' . $this->t('{core:no_state:causes}') . '</h3>');
echo('<ul>');
echo('<li>' . $this->t('{core:no_state:cause_backforward}') . '</li>');
echo('<li>' . $this->t('{core:no_state:cause_openbrowser}') . '</li>');
echo('<li>' . $this->t('{core:no_state:cause_nocookie}') . '</li>');
echo('</ul>');


/* Add error report submit section if we have a valid technical contact. */
if (isset($this->data['errorReportAddress'])) {

	echo('<h2>' . $this->t('{core:no_state:report_header}') . '</h2>');

	echo('<form action="' . htmlspecialchars($this->data['errorReportAddress']) . '" method="post">');

	echo('<p>' . $this->t('{core:no_state:report_text}') . '</p>');
	echo('<p>' . $this->t('{errors:report_email}') . '<input type="text" size="25" name="email" value="' . htmlspecialchars($this->data['email']) . '"/></p>');

	echo('<p>');
	echo('<textarea style="width: 300px; height: 100px" name="text">' . $this->t('{errors:report_explain}') . '</textarea>');
	echo('</p>');
	echo('<p>');
	echo('<input type="hidden" name="reportId" value="' . $this->data['reportId'] . '" />');
	echo('<input type="submit" name="send" value="' . $this->t('{errors:report_submit}') . '" />');
	echo('</p>');
	echo('</form>');
}

$this->includeAtTemplateBase('includes/footer.php');
