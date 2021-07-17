var LoaArticleTracker = ( function( LoaArticleTracker ) {


	window.onload = () => {
		LoaArticleTracker.Overlord.init()
	}


	LoaArticleTracker.Overlord = {
		app: null,

		init() {
			const settings = this.getSettings()

			this.app = new Vue( settings )
		},


		getSettings() {
			return {
				el: 	'#app',
				data: 	{
					errorMsg: 	'',
					hasChanged:	false,
					newAuthKey: '',
					successMsg: '',
				},
				methods: {
					handleInput: function( input ) {
						this.successMsg = ''

						this.newAuthKey = input.currentTarget.value.trim()
						this.checkForErrors( this.newAuthKey )
					},

					checkForErrors( authKey ) {
						if( 'string' !== typeof( authKey ) || !authKey.length ) {
							this.errorMsg = 'Please enter an authorization key'

						} else if( 8 > authKey.replace( /\s/g, '' ).length ) {
							this.errorMsg = 'Authorization key should be at least eight characters (excluding white space)'

						} else {
							this.errorMsg 	= ''
							this.hasChanged = true
						}
					},

					submitAuthKey: function() {
						this.checkForErrors( this.newAuthKey )
						if( this.errorMsg.length || !this.hasChanged ) {
							return
						}

						let request = fetch( 
							LoaArticleTrackerSettings.ajax_url,
							{
								method: 'POST',
								body: 	new FormData( this.$el.querySelector('form') ),
							}
						)
						
						request
							.then( request => request.json() )
							.then( response => {
								if( !response.hasOwnProperty( 'success' ) || !response.hasOwnProperty( 'data' ) ) {
									this.errorMsg = 'Failed to submit'
								} else if( false === response.success ) {
									this.errorMsg = response.data
								} else {
									this.successMsg = response.data
									this.reset()
								}
							})
					},

					reset: function() {
						this.errorMsg = ''
						this.hasChanged = false
						this.newAuthKey = ''
						
						setTimeout( () => {
							this.successMsg = ''
						}, 2500 )
					}
				},
			}
		}
	}
	


	return LoaArticleTracker

}) ( LoaArticleTracker || {} )