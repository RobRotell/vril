import sharp from 'sharp';
import { promises } from 'fs';
import path from 'path';
import FileType from 'file-type';


( async () => {

	const assetsDir = './wp/app/media'

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
		const fileType = await FileType.fromFile( path )

		if( undefined !== fileType && ( 'image/jpeg' === fileType.mime || 'image/png' === fileType.mime ) ) {
			createWebp( path )
			createAvif( path )
		}
	}


	const createWebp = async( path ) => {
		const newFilePath = path.replace( /\.[^/.]+$/, '.webp' )

		sharp( path )
			.webp({
				nearLossless: true
			})
			.toFile( newFilePath, err => console.error( err ) )
	}


	const createAvif = async( path ) => {
		const newFilePath = path.replace( /\.[^/.]+$/, '.avif' )

		sharp( path )
			.avif({
				lossless: true	
			})
			.toFile( newFilePath, err => console.error( err ) )
	}


	readAssetDir( assetsDir ).catch( err => console.log( err ) )


}) () 
