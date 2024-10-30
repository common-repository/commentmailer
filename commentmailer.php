<?php
/*
Plugin Name: CommentMailer
Plugin URI: http://hudatoriq.web.id/wp-hacks/commentmailer
Description: Send email after replying a comment on the blog comment system.
Author: Huda Toriq
Version: 0.1
Author URI: http://hudatoriq.web.id
*/

class cmtMailer {
	function install() {
		if(!$this->get_options()) {
			$this->setdefault();
		}
	}
	function uninstall() {
		header('Location: ' . get_bloginfo('url') . '/wp-admin/plugins.php?action=deactivate&plugin=commentmailer.php');
		die();
	}
	function setdefault() {
		$options = array(
			"name" => "", 
			"email" => "", 
			"composition" => "%previousmessage%\n\n%currentreply%\n\n%signature%\n\n%separator%\n%notes%", 
			"signature" => "", 
			"subject" => "",
			"autoinsert" => 1, 
			"sort" => "descending"
		);
		update_option('commentmailer', $options);
	}
	function get_options() {
		if(empty($this->options)) {
			$this->options = get_option('commentmailer');
		}
	}
	function update_options() {
		load_plugin_textdomain('commentmailer', PLUGINDIR);
		$this->get_options();
		$newvalue['name'] = $_POST['name'];
		$newvalue['email'] = $_POST['email'];
		$newvalue['subject'] = $_POST['subject'];
		$newvalue['composition'] = $_POST['composition'];
		$newvalue['signature'] = $_POST['signature'];
		$newvalue['autoinsert'] = $_POST['autoinsert'];
		$newvalue['sort'] = $_POST['sort'];
		if($newvalue['email'] && !is_email($newvalue['email'])) {
			$this->note = __('Please specify a valid email address!', 'commentmailer');
		} elseif($this->options != $newvalue) {
			if(update_option('commentmailer', $newvalue)) {
				$this->note = __('Successfully updating your options', 'commentmailer');
			} else {
				$this->note = __('There is something wrong when we are updating your options', 'commentmailer');
			}
		} else {
			$this->note = __('You don&#8217;t have anything to update', 'commentmailer');
		}
	}
	function init() {
		if($_POST['cmailer_submit'] and is_admin()) {
			$this->update_options();			
		} elseif($_POST['cmailer_uninstall'] and is_admin()) {
			$this->uninstall();
		}
		if($_POST['cmailer_lang']) {
			$this->get_options();
			$this->emaillang = $_POST['cmailer_lang'];
		}
	}
	function get_comments($comments) {
		$this->comments = $comments;
		return $comments;
	}
	function form($post_ID) {
		load_plugin_textdomain('commentmailer', PLUGINDIR);
		global $current_user;
		if($current_user->ID) {
			$this->get_options();
			?>
			<div id="commentmailerto">
			<p><?php _e('Also send this reply via e-mail to the following commenters:', 'commentmailer'); ?><br />
			<select name="commenters[]" multiple id="commenters" size="4">
				<option value="0" selected="selected"><?php _e('-- None --', 'commentmailer'); ?></option>
			<?php 
			if($this->options['sort'] = "descending") {
				krsort($this->comments);
			} else {
				ksort($this->comments);
			}
			foreach ($this->comments as $comment) {
				echo('<option value="'.$comment->comment_ID.'">'.$comment->comment_author.'</option>');
			}
			?>
			</select>
			</p>
			</div>
			<?php
			$emaillanguages = $this->get_locales();
			if(!empty($emaillanguages)) {
			?>
			<div id="commentmailerlang">
			<p><?php _e('E-mail language:', 'commentmailer'); ?><br />
			<select name="cmailer_lang" id="cmailer_lang">
			<?php foreach($emaillanguages as $localecode) {
				?>
				<option value="<?php echo($localecode); ?>"><?php echo($localecode); ?></option>
				<?php 
			}
			?>
			</select>
			</p>
			</div>
			<?php 
			}
			?>
			<p><a href="<?php echo(get_bloginfo('url') . "/wp-admin/options-general.php?page=commentmailer") ?>" target="_blank"><?php _e('Settings', 'commentmailer'); ?></a></p>
		<?php
		}
	}
	function check_comment($reply_ID) {
		global $commentdata;
		if($commentdata['user_ID'] && $_POST['commenters']) {
			$this->construct_email($reply_ID);
		}
	}
	function construct_email($reply_ID) {
		global $commentdata, $locale;
		$defaultlocale = get_locale();
		if($_POST['cmailer_lang']) {
			$locale = $_POST['cmailer_lang'];
		}
		load_plugin_textdomain('commentmailer', PLUGINDIR);
		$locale = get_locale();
		$this->get_options();
		if($this->options['email']) {
			if($this->options['name']) {
				$this->header['from'] = "\"" . $this->options['name'] . "\" <" . $this->options['email'] . ">";
			} else {
				$this->header['from'] = $this->options['email'];
			}
		} else {
			$this->header['from'] = "\"" . $commentdata['comment_author'] . "\" <" . $commentdata['comment_author_email'] . ">";
		}
		$this->header['replyto'] = $this->header['from'];
		$this->header['subject'] = $this->options['subject'] ? $this->options['subject'] : __('Re: Blog comment', 'commentmailer');
		$this->body['message'] = stripslashes(strip_tags($commentdata['comment_content']));
		$this->body['message'] = wordwrap($this->body['message'], 75, "\n", 10);
		$this->body['signature'] = $this->options['signature'];
		$this->body['separator'] = "---------------------------------------------------------------------------";
		$commenters = $_POST['commenters'];
		foreach ($commenters as $comment_ID) {
			$comment = get_comment($comment_ID);
			$comment_text = strip_tags($comment->comment_content);
			$comment_text = wordwrap($comment_text, 73, "\n", 10);
			$comment_text = "> " . str_replace("\n", "\n> ", $comment_text);
			$notes =	sprintf(__("This e-mail is generated and sent to you because a blog author wants to inform you that he/she has replied your comment on %s", "commentmailer"), get_bloginfo("url")) . "\n\n" .
				__("You can always view your comment and its reply on the following URLs:", "commentmailer") . "\n" .
				__("Your comment:", "commentmailer") . " " . get_bloginfo('url') . "?p=" . $comment->comment_post_ID . "#comment-" . $comment_ID . "\n" .
				__("Response to your comment:", "commentmailer") . " " . get_bloginfo('url') . "?p=" . $comment->comment_post_ID . "#comment-" . $reply_ID . "\n\n" .
				__("You can continue the discussion by writing another comment in the same post.", "commentmailer");
			
			$this->header['to'] = $comment->comment_author_email;
			$this->body['previous_message'] = sprintf(__('On %s you wrote:', 'commentmailer'), mysql2date(__('F j, Y', 'commentmailer'), $comment->comment_date)) . "\n" . $comment_text;
			$this->body['notes'] = wordwrap($notes, 75, "\n", 10);
			$this->emailbody = str_replace('%previousmessage%', $this->body['previous_message'], $this->options['composition']);
			$this->emailbody = str_replace('%currentreply%', $this->body['message'], $this->emailbody);
			$this->emailbody = str_replace('%signature%', $this->body['signature'], $this->emailbody);
			$this->emailbody = str_replace('%separator%', $this->body['separator'], $this->emailbody);
			$this->emailbody = str_replace('%notes%', $this->body['notes'], $this->emailbody);

			$this->emailheader = "MIME-Version: 1.0\n" .
				"From: " . $this->header['from'] . "\n" .
				"Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
			$this->sendmail();
		}
	}
	function sendmail() {
		if(wp_mail($this->header['to'], $this->header['subject'], $this->emailbody, $this->emailheader)) {
		} else {
		}
	}
	function admin_menu() {
		load_plugin_textdomain('commentmailer', PLUGINDIR);
		add_options_page(__('Comment Mailer', 'commentmailer'), __('Comment Mailer', 'commentmailer'), 10, 'commentmailer', array(& $this, 'commentmailer_option'));
	}
	function select() {
		$selected = ' checked="checked"';
		if($this->options['autoinsert']) {
			$this->select['autoinsert1'] = $selected;
			
		} else {
			$this->select['autoinsert0'] = $selected;
		}
		if($this->options['sort'] == 'descending') {
			$this->select['descending'] = $selected;
		} else {
			$this->select['ascending'] = $selected;
		}
	}
	
	function get_locales() {
		global $locale;
		if ($handle = opendir(PLUGINDIR)) {
			$locales = array();
			while (false !== ($file = readdir($handle))) { 
				if(is_file(ABSPATH . PLUGINDIR . '/' . $file)) {
					if(preg_match("/^commentmailer-(([a-z]{2})(_([A-Z]{2}))?).mo$/", $file, $match)) {
						$locales[] = $match[1];
					}
				}
			}
    	closedir($handle); 
			if(!in_array("en_US", $locales)) {
				$locales[] = "en_US";
			}
			sort($locales);
		}
		return $locales;
	}
	
	function commentmailer_option() {
		load_plugin_textdomain('commentmailer', PLUGINDIR);
		$this->get_options();
		$this->select();
		if($this->note) {
			?>
			<div id="message" class="updated fade"><p><strong><?php echo($this->note); ?></strong></p></div>
			<?php
		}
		?>
		<div class="wrap">
			<h2><?php _e('Comment Mailer', 'commentmailer'); ?></h2>
			<form method="post" action="">
				<p class="submit"><input type="submit" name="cmailer_submit" value="<?php _e('Update Options &raquo;', 'commentmailer'); ?>" /></p>
				<fieldset class="options">
				<legend><?php _e('E-mail Settings', 'commentmailer'); ?></legend>
				<table class="optiontable"> 
					<tr valign="top">
						<th scope="row"><?php _e('Sender e-mail:', 'commentmailer') ?></th>
						<td colspan="3"><input type="text" name="email" id="email" size="30" value="<?php echo($this->options['email']); ?>" /><br />
						<?php _e('If empty, your email address in your user profile will be used instead', 'commentmailer'); ?><br />
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Sender name:', 'commentmailer') ?></th>
						<td colspan="3"><input type="text" name="name" id="name" size="30" value="<?php echo($this->options['name']); ?>" /><br />
						<?php _e('If the above e-mail address is empty, your display name in your user profile will be used instead', 'commentmailer'); ?><br />
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Subject:', 'commentmailer') ?></th>
						<td colspan="3"><input type="text" name="subject" id="subject" size="30" value="<?php echo($this->options['subject']); ?>" /><br />
						<?php printf(__("If empty, '%s' will be used instead", "commentmailer"), __('Re: Blog comment', 'commentmailer')); ?><br />
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('E-mail composition:', 'commentmailer') ?></th> 
						<td colspan="3"><textarea name="composition" cols="30" rows="5" class="code"><?php echo($this->options['composition']); ?></textarea><br />
						<?php printf(__('Plain text only. Each linebreak counts. Use these tags to help you construct your e-mail message: %s', 'commentmailer'), '<code>%previousmessage%, %currentreply%, %signature%, %separator% and %notes%</code>'); ?>
					</tr>
					<tr valign="top"> 
						<th scope="row"><?php _e('E-mail signature:', 'commentmailer') ?></th> 
						<td colspan="3"><textarea name="signature" cols="30" rows="5" class="code"><?php echo($this->options['signature']); ?></textarea><br />
						<?php printf(__('Plain text only', 'commentmailer')); ?>
					</tr>
				</table>
				</fieldset>
				<fieldset class="options">
				<legend><?php _e('Menu Settings', 'commentmailer'); ?></legend>
				<table class="optiontable"> 
					<tr valign="top">
						<th scope="row"><?php _e('Automatically insert a list menu:', 'commentmailer') ?></th> 
						<td colspan="3">
							<p><label><input type="radio"<?php echo $this->select['autoinsert1']; ?> name="autoinsert" id="autoinsert" value="1" /> <?php _e('yes (default)', 'commentmailer'); ?></label></p>
							<p><label><input type="radio"<?php echo($this->select['autoinsert0']); ?> name="autoinsert" id="autoinsert" value="0" /> <?php _e('no', 'commentmailer'); ?></label></p>
							<p><?php _e("If you select 'yes', it will insert a multiple selection list on <code>comment_form</code> hook. Make sure your theme has the hook.", "commentmailer"); ?></p>
						</td>
					</tr>
					<tr valign="top"> 
						<th scope="row"><?php _e('Sort commenters:', 'commentmailer') ?></th> 
						<td colspan="3">
							<p><label><input type="radio"<?php echo($this->select['descending']); ?> name="sort" id="sort" value="descending" /> <?php _e('descending (default)', 'commentmailer'); ?></label></p>
							<p><label><input type="radio"<?php echo($this->select['ascending']); ?> name="sort" id="sort" value="ascending" /> <?php _e('ascending', 'commentmailer'); ?></label></p>
						</td>
					</tr>
				</table>
				</fieldset>
				<p class="submit"><input type="submit" name="cmailer_submit" value="<?php _e('Update Options &raquo;', 'commentmailer'); ?>" /></p>
			</form>
		</div>
		<div class="wrap">
			<h2><?php _e('Uninstall', 'commentmailer'); ?></h2>
				<form method="post" action="">
					<p><?php _e('If you want to remove this plugin, simply click this button. It will deactivate the plugin and remove all related settings, leaving no pain in the database.', 'commentmailer'); ?></p>
					<p class="submit">
						<input type="submit" name="cmailer_uninstall" value="Uninstall" />
					</p>	
					<p><strong><?php _e('Attention! It can not be undone.', 'commentmailer'); ?></strong></p>
				</form>
		</div>
		<?php
	}
	function cmtMailer() {
		if(is_admin()) {
			add_action('admin_menu', array(& $this, 'admin_menu'));
			add_action('activate_commentmailer.php', array(& $this, 'install'));
		}
		add_action('comment_post', array(& $this, 'check_comment'));
		add_action('init', array(& $this, 'init'));
		add_action('comment_form', array(& $this, 'form'));
		add_filter('comments_array', array(& $this, 'get_comments'));
	}
}

$cmtMailer = new cmtMailer();

/* Use this template tag in your theme if you don't have the required template hook */
function commentmailer_form() {
	global $post_ID, $cmtMailer;
	$cmtMailer->form($post_ID);
}
