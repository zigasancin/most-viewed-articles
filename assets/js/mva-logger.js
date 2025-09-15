document.addEventListener( 'DOMContentLoaded', function () {
	if ( mvaLogger && mvaLogger.postId ) {
		fetch( mvaLogger.restUrl, {
			method: 'POST',
			headers: {
				'X-MVA-Nonce': mvaLogger.nonce,
				'Content-Type': 'application/json'
			},
			body: JSON.stringify( { post_id: mvaLogger.postId } )
		} );
	}
} );