<?php

function get_svn_revision() {
	return SVNAutoUpgrader::getSvnRevision();
}

function get_svn_branch() {
	return SVNAutoUpgrader::getSvnBranch();
}

?>