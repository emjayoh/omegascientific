<?php
/*
Plugin Name: SVN Auto Upgrade
Description: Hook into plugin and core upgrader to support SVN driven sites. Now you can freely use the WordPress auto upgrade features without worrying about the Subversion impact. Just upgrade in WordPress and then commit the changes in SVN. Requires that your system permissions allow shell_exec() to execute svn commands.
Author: Modern Tribe, Inc.
Version: 1.2
Author URI: http://tri.be
*/

if ( !class_exists( 'SVNAutoUpgrader' ) ) {
	class SVNAutoUpgrader {
		
		private static $instance;

		/**
		 * Enforce Singleton Pattern
		 */
		public function getInstance() {
			if(null == self::$instance) {
				self::$instance = new SVNAutoUpgrader;
			}
			return self::$instance;
		}
		
		public function __construct() {
			add_filter('upgrader_pre_install', array(__CLASS__, 'makeSvnBackup'), 10, 2);
			add_filter('upgrader_post_install', array(__CLASS__, 'restoreSvnFolders'), 10, 2);
			add_filter('admin_footer_text', array(__CLASS__, 'adminPrintSvnInfo'));
			add_filter('bloginfo', array(__CLASS__, 'overwriteVersion'), 20, 2);
			add_action('admin_footer', array(__CLASS__, 'globalSvnAddRemove'));
		}	
		
		public static function makeSvnBackup( $return, $targets) {
			global $wp_filesystem;
			// copy folder to maintain svn structure
			if ( isset( $targets['plugin'] ) || isset( $targets['theme'] ) ) {
				if ( isset( $targets['plugin'] ) ) {
					$upgrade_dir = $wp_filesystem->wp_plugins_dir() . dirname( $targets['plugin'] );
				} elseif ( isset( $targets['theme'] ) ) {
					$upgrade_dir = $wp_filesystem->wp_themes_dir() . $targets['theme'];					
				}
				if ( self::svnTest( $upgrade_dir ) ) {
					self::recurseSvnCopy( $upgrade_dir, $upgrade_dir.'.temp' );
				}
			}

			return $return;
		}

		/**
		 * Restore contents from .temp folder
		 *
		 * @param boolean $return
		 * @param array $targets
		 * @return boolean $return
		 */
		public static function restoreSvnFolders( $return, $targets ) {
			// restore svn structure
			global $wp_filesystem;
			// copy folder to maintain svn structure
			if ( isset( $targets['plugin'] ) || isset( $targets['theme'] ) ) {
				if ( isset( $targets['plugin'] ) ) {
					$upgrade_dir = $wp_filesystem->wp_plugins_dir() . dirname( $targets['plugin'] );
				} elseif ( isset( $targets['theme'] ) ) {
					$upgrade_dir = $wp_filesystem->wp_themes_dir() . $targets['theme'];					
				}
				if ( is_dir( $upgrade_dir.'.temp' ) && self::svnTest( $upgrade_dir.'.temp' ) ) {
					self::recurseSvnCopy( $upgrade_dir.'.temp', $upgrade_dir );
					$wp_filesystem->rmdir( $upgrade_dir.'.temp', true );
					self::svnAddRemove( $upgrade_dir );
				}
			}
			return $return;
		}

		/**
		 * Copy .svn folders recursively
		 *
		 * @param string $src folder path
		 * @param string $dst folder path
		 * @param boolean $in_svn is this folder in svn
		 * @return void
		 */
		public static function recurseSvnCopy( $src, $dst, $in_svn = false ) {
			if (!is_dir($dst)) { @mkdir($dst); }
		    $dir = opendir($src);
		    while(false !== ( $file = readdir($dir)) ) {
		        if (( $file != '.' ) && ( $file != '..' ) ) {
					if ( $file == '.svn' ) {
		                self::recurseSvnCopy($src . '/' . $file, $dst . '/' . $file, true);						
					} elseif ( is_dir($src . '/' . $file) ) {
		                self::recurseSvnCopy($src . '/' . $file, $dst . '/' . $file, true);
		            } elseif ($in_svn) {
		                copy($src . '/' . $file, $dst . '/' . $file);
		            }
		        }
		    }
		    closedir($dir);
		}
		
		/**
		 * SVN Add and Remove all relevant files in an upgrade.
		 *
		 * @return void
		 */
		public static function globalSvnAddRemove() {
			$action = isset($_GET['action']) ? $_GET['action'] : 'upgrade-core';
			if ( 'do-core-upgrade' == $action ) {
				check_admin_referer('upgrade-core');
				self::svnAddRemove( ABSPATH );
			}
		}
		
		/**
		 * SVN Add and Remove all relevant files in a folder
		 *
		 * @param string $path folder path
		 * @return void
		 */
		public static function svnAddRemove( $path ) {
			if (self::svnTest($path)) {
				$path = escapeshellcmd( $path );
				shell_exec("svn add `svn status $path | grep '^?' | awk '{ print $2 }' | xargs`");
				shell_exec("svn delete --force `svn status $path | grep '^!' | awk '{ print $2 }' | xargs`");
			}
		}
		
		/**
		 * Check that the path is using SVN
		 *
		 * @param string $path folder path
		 * @return boolean
		 */
		public static function svnTest( $path ) {
			return is_dir( trailingslashit($path) . '.svn' );
		}

		/**
		 * print SVN info into footer of admin
		 *
		 * @param string $footer filtered footer input
		 * @return void
		 * @author Shane & Peter, Inc. (Peter Chester)
		 */
		public static function adminPrintSvnInfo($footer) {
			$output = '<span class="svn-info-admin">';
			$output .= 'SVN: r' . self::getSvnRevision();
			$branch = self::getSvnBranch();
			if ($branch) { $output .= " ($branch)"; }
			$output .= '</span>';
			return $output;
		}

		/**
		 * Get SVN Revision
		 *
		 * @return void
		 * @author Shane & Peter, Inc. (Peter Chester)
		 */
		public static function getSvnRevision() {
			$svninfo = self::getSvnInfo();
			return $svninfo['version'];
		}

		/**
		 * Get SVN Branch
		 *
		 * @return void
		 * @author Shane & Peter, Inc. (Peter Chester)
		 */
		private static function getSvnBranch() {
			$svninfo = self::getSvnInfo();
			return $svninfo['branch'];
		}

		/**
		 * Get SVN info.  Set REVISION and BRANCH if available
		 *
		 * @return void
		 * @author Shane & Peter, Inc. (Peter Chester)
		 */
		private static function getSvnInfo() {
			$svninfo = get_option('SVN_Info');

			if ($svninfo['timeout'] < mktime()) {
				if (file_exists(ABSPATH . '.svn/entries')) {
					$svn = file(ABSPATH . '.svn/entries');

					$info = array();
					if (is_numeric(trim($svn[3]))) {
						$version = intval($svn[3]);
						$branch = trim($svn[4]);
					} else { // pre 1.4 svn used xml for this file
						$version = explode('"', $svn[4]);
						$version = $version[1];
					}

					$info = array('version'=>$version,'branch'=>$branch);
				} elseif(file_exists(ABSPATH.'svn-info.data')) {
					$svn = file(ABSPATH.'svn-info.data');
					if(is_numeric(trim($svn[0]))) {
						$version = intval($svn[0]);
						$branch = trim($svn[1]);
					}
					$info = array('version'=>$version,'branch'=>$branch);
				}

				if($info) {
					$info['timeout'] = mktime() + 60;
					update_option('SVN_Info', $info);

					$svninfo = $info;
				}
			}

			return $svninfo;
		}

		/**
		 * Update Wordpress Version in Blog Info
		 *
		 * @param string $output original output
		 * @param string $type type of bloginfo
		 * @return string $output filtered output
		 */
		public static function overwriteVersion( $output, $type ) {
			if ($type == 'version') {
				$output = self::getSvnRevision();
			}
			return $output;
		}

	}

	SVNAutoUpgrader::getInstance();
	require_once('lib/template-tags.php');
}
?>