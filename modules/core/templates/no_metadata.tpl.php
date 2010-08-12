<?php

$this->data['header'] = $this->t('{core:no_metadata:header}');
$this->includeAtTemplateBase('includes/header.php');

echo('<h2>' . $this->t('{core:no_metadata:header}') . '</h2>');
echo('<p>' . $this->t('{core:no_metadata:not_found_for}') . '</p>');
echo('<code style="margin-left: 3em;">' . htmlspecialchars($this->data['entityId']) . '</code>');
echo('<p>' . $this->t('{core:no_metadata:config_problem}') . '</p>');

echo('<ul>');
echo('<li>' . $this->t('{core:no_metadata:suggestion_user_link}') . '</li>');
echo('<li>' . $this->t('{core:no_metadata:suggestion_developer}') . '</li>');
echo('</ul>');

$this->includeAtTemplateBase('includes/footer.php');
