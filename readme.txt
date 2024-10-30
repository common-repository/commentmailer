=== CommentMailer ===
Contributors: Huda Toriq
Tags: comment, email, e-mail, discussion
Requires at least: 2.0.2
Tested up to: 2.3.1
Stable tag: 0.1

Send automated email to your blog's commenters everytime you reply their comments on the blog's comment system.

== Description ==

CommentMailer is a WordPress plugin that generates and sends e-mail to your blog's commenters to tell them that you
have replied his/her comment on your blog's comment system. It adds extra options on your blog's comment form to select
which commenters you are replying to. Submitting the reply comment will trigger the e-mail generation function thus 
send them automatically. You can customize your own notification e-mail or use the default one.

== Installation ==

1. Download the archive file and decompress it.
2. Upload the file named `commentmailer.php` into your `/wp-content/plugins/` directory. Remember, the `commentmailer.php` file should reside directly in the /plugins/ directory, not in subdirectory of it.
3. Activate the plugin through the `Plugins` menu in WordPress.
4. Now, each time you want to reply someone's comment on the comment system, you will see a multiple selection list consisting all visitors's name who throwed a comment on the current post. Select which commenters you are replying to.

== Frequently Asked Questions ==

= Why I don't see any extra options on the comment submission form? =

It's probably because your theme doesn't have the 'comment_form' hook. I use this hook to automatically insert 
a menu to select which commenters you are replying to. If it's the problem, just an extra template tag. Open your theme
file (usually comments.php) And insert the following template tag: ``<?php commentmailer_form(); ?>`` between the `<form>` and `</form>` tags.
