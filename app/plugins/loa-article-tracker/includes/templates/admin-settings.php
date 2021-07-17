<style>
	.msg--error {
		color: red;
	}

	.msg--success {
		color: green;
	}

</style>


<div id="app" class="wrap">
	<h1>Article Tracker Settings</h1>

	<form v-on:submit.prevent>
		<?php wp_nonce_field( self::$nonce_action, self::$nonce_name ); ?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="authkey">Authorization Key</label>
					</th>
					<td>
						<input 
							id="authkey" 
							class="regular-text" 
							type="text" 
							name="auth_key"
							v-bind:value="newAuthKey"
							v-on:input="handleInput"
							placeholder="Save new authorization key &hellip;" 
							required
						/>
						<input type="hidden" name="action" value="loa_article_tracker_update_auth_key" />
					</td>
				</tr>
			</tbody>
		</table>
		<div>
			<p
				class="msg msg--error"
				v-show="errorMsg.length"
			>{{ errorMsg }}</p>
			<button 
				id="submit" 
				class="button button-primary" 
				type="submit" 
				:disabled="!hasChanged"
				v-on:click="submitAuthKey"
			>Save</button>
			<p
				class="msg msg--success"
				v-show="successMsg.length"
			>{{ successMsg }}</p>
		</div>
	</form>
</div>