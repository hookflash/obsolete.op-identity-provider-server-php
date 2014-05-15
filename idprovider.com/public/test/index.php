<?php include( dirname(dirname(dirname(__FILE__))) . '/app/php/config/config.php' ); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Example Identity Provider TESTER</title>

    <script type="text/javascript" src="../js/lib/jquery/jquery-1.8.3.min.js"></script>

	<style type="text/css">
		#frm_req, #frm_resp {
			font-size: 20px;
			font-weight: bold;
			width: 1024px;
			height: 200px;
			background: #fcc;
		}
	</style>
  </head>

  <body>
	<select id="frm_type">
		<option value="POST">POST</option>
	</select>
	
	<select id="frm_fileType">
		<option value="JSON">JSON</option>
		<option value="XML">XML</option>
	</select>	
	<hr/>
	
	Request:<br/>
	<textarea id="frm_req"> <?php echo trim(htmlentities('')); ?> </textarea>
	
	<select id="frm_method">
		<option value="services-get">services-get</option>
		<option value="login">login</option>
		<option value="server-nonce-get">server-nonce-get</option>
		<option value="identity-salts-get">identity-salts-get</option>
		<option value="identity-salts-set">identity-salts-set</option>
		<option value="oauth-provider-authentication">oauth-provider-authentication</option>
                <option value="hosting-data-get">hosting-data-get</option>
		<option value="linkedin-token-exchange">linkedin-token-exchange</option>
		<option value="sign-up">sign-up</option>
		<option value="profile-get">profile-get</option>
		<option value="profile-update">profile-update</option>
		<option value="password-change">password-change</option>
		<option value="pin-validation">pin-validation</option>
		<option value="lockbox-half-key-store">lockbox-half-key-store</option>
		<option value="identity-access-validate">identity-access-validate</option>
		<option value="identity-access-rolodex-credentials-get">identity-access-rolodex-credentials-get</option>
                <option value="federated-contacts-get">federated-contacts-get</option>
                <option value="devtools-database-clean-provider">devtools-database-clean-provider</option>
		<option value="internal_cslfp">internal_cslfp</option>
		<option value="internal_thfs">internal_thfs</option>
		<option value="internal_ciasp">internal_ciasp</option>
                <option value="internal_prct">internal_prct</option>
                <option value="internal_erct">internal_erct</option>
	</select>
	
	<input type="button" id="demoreq" value="REQ template"/>
	<hr/>
	
	Response:<br/>
	<textarea id="frm_resp"></textarea>
	<hr/>
	
	<input type="button" id="btn_start" value="GO"/><br/>
	
	
	<script type="text/javascript">	
	$(document).ready(function() {

		$('#btn_start').click( function() {
	
			$.ajax({
				url: '<?php echo 'http://' . $_SERVER['HTTP_HOST'] ?>/api.php',
				type: $('#frm_type').val(),
				data: $('#frm_req').val(),
				success: function(data, textStatus, jqXHR) {
					$('#frm_resp').val(data);
	
					$('#frm_resp').css('background-color', '#ffffff')
					$('#frm_resp').animate({
						backgroundColor: '#ffcccc',
					}, 2000, function() {
					});
				}
			});
		});
		
		$('#demoreq').click( function() {
			
			$.ajax({
				url: '<?php echo 'http://' . $_SERVER['HTTP_HOST'] ?>' + "/test/load_request.php?method=" + escape ( $('#frm_method').val() ) + "&fileType=" + escape ( $('#frm_fileType').val() ),
				type: 'GET',
				success: function(data, textStatus, jqXHR) {
					$('#frm_req').val(data);
				}
			});
		});
	
	});	
	</script>

  </body>

</html>
