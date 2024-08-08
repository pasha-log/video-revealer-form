## WordPress Video Revealer Form Plugin
**Description:**

Built a plugin that is a contact form that reveals a hidden [unlisted](https://support.google.com/youtube/answer/157177?hl=en&co=GENIE.Platform%3DDesktop#zippy=%2Cunlisted-videos) YouTube video that informs realtors of Home Equity Conversion Mortgage for Home Purchase (H4P). Implemented robust security measures: 
* Client-side and server-side validation to ensure data integrity. 
* Nonce verification for enhanced form security. 
* Sanitization of input fields using sanitize functions. 
* Integrated v3 reCAPTCHA to prevent spam submissions. 
* Encrypted sensitive contact information stored in the database.

After the video is revealed, the plugin uses the [YouTube Player API Reference for iframe Embeds](https://developers.google.com/youtube/iframe_api_reference) to determine when the player reaches the end to conditionally render buttons leading to further resources.

The form can be deployed anywhere on a WordPress site using the shortcode `[video_revealer_form]`. 

[LIVE DEMO](https://signetmortgage.com/staging/realtors-h4p-invite/)

**Technologies Used:**

- PHP
- SQL
- jQuery
- CSS
- HTML
- [WordPress Developer Resources Functions](https://developer.wordpress.org/reference/functions/)
- [YouTube Player API Reference for iframe Embeds](https://developers.google.com/youtube/iframe_api_reference)

The landing page. The HTML markup is not included in this repository.
<img src='./screenshots/Screenshot (474).png' alt=''> 

---

The modal containing a shortcode of the video revealer form.
<img src='./screenshots/Screenshot (475).png' alt=''>

---

The video is revealed after form submission, which programmatically reveals more resources by the end of the video. The video's featured image is partially buffed out for privacy reasons.
<img src='./screenshots/Screenshot (483).png' alt=''>

---

The settings page for the submissions, where admin can easily switch out the video link, change who receives email notifications, and see all submissions with the choice to delete any of them. Most of the information has been buffed out for privacy.
<img src='./screenshots/Screenshot (476).png' alt=''>
