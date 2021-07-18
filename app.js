import sharp from 'sharp';
import { promises } from 'fs';
import path from 'path';


( async () => {

	const assetsDir = './app/assets'


	const readAssetDir = async ( arg ) => {
		const entries = await promises.readdir( arg )

		for( const entry of entries ) {
			const entryPath = path.join( arg, entry ),
				stat 		= await promises.stat( entryPath )

			if( stat.isFile() ) {
				convertFile( entryPath )

			} else if( stat.isDirectory() ) {
				readAssetDir( entryPath )
			}
		}
	}


	const convertFile = async ( path ) => {

		console.log( path )

		doMe()
		


	}

	readAssetDir( assetsDir ).catch( console.error )



	// const dir = await fs.promises.opendir( )

	// const file = './the-dry-poster.jpg'
	
	// sharp( file )
	// 	.avif()
	// 	.toFile( './output.avif', err => {
	// 		console.log( err )
	// 	})

}) () 
