/**
 * i-downloads/download-list — Block editor component.
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	RangeControl,
	ToggleControl,
	Spinner,
	Placeholder,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

export default function Edit( { attributes, setAttributes } ) {
	const { category, includeSubcategories, tag, limit, orderby, order, layout, showSearch } = attributes;
	const blockProps = useBlockProps();

	// Fetch categories from the WP data store (hits /wp/v2/idl_category)
	const categories = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecords( 'taxonomy', 'idl_category', {
				per_page: -1,
				orderby: 'name',
				order: 'asc',
				_fields: 'id,name,parent',
			} ),
		[]
	);

	// Fetch tags
	const tags = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecords( 'taxonomy', 'idl_tag', {
				per_page: 100,
				orderby: 'name',
				order: 'asc',
				_fields: 'id,name',
			} ),
		[]
	);

	const categoryOptions = [
		{ label: __( '— All categories —', 'i-downloads' ), value: 0 },
		...( categories ?? [] ).map( ( cat ) => ( {
			label: ( cat.parent ? '— ' : '' ) + cat.name,
			value: cat.id,
		} ) ),
	];

	const tagOptions = [
		{ label: __( '— All tags —', 'i-downloads' ), value: 0 },
		...( tags ?? [] ).map( ( t ) => ( { label: t.name, value: t.id } ) ),
	];

	const isLoading = ! categories || ! tags;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Filter', 'i-downloads' ) }>
					{ isLoading ? (
						<Spinner />
					) : (
						<>
							<SelectControl
								label={ __( 'Category', 'i-downloads' ) }
								value={ category }
								options={ categoryOptions }
								onChange={ ( val ) => setAttributes( { category: Number( val ) } ) }
							/>
							<ToggleControl
								label={ __( 'Include subcategories', 'i-downloads' ) }
								help={ __( 'When on, downloads from every descendant category are listed too.', 'i-downloads' ) }
								checked={ includeSubcategories }
								onChange={ ( val ) => setAttributes( { includeSubcategories: val } ) }
								disabled={ ! category }
							/>
							<SelectControl
								label={ __( 'Tag', 'i-downloads' ) }
								value={ tag }
								options={ tagOptions }
								onChange={ ( val ) => setAttributes( { tag: Number( val ) } ) }
							/>
						</>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Display', 'i-downloads' ) }>
					<RangeControl
						label={ __( 'Number of items', 'i-downloads' ) }
						value={ limit }
						onChange={ ( val ) => setAttributes( { limit: val } ) }
						min={ 1 }
						max={ 50 }
					/>
					<SelectControl
						label={ __( 'Layout', 'i-downloads' ) }
						value={ layout }
						options={ [
							{ label: __( 'List', 'i-downloads' ),  value: 'list' },
							{ label: __( 'Grid', 'i-downloads' ),  value: 'grid' },
							{ label: __( 'Table', 'i-downloads' ), value: 'table' },
						] }
						onChange={ ( val ) => setAttributes( { layout: val } ) }
					/>
					<SelectControl
						label={ __( 'Order by', 'i-downloads' ) }
						value={ orderby }
						options={ [
							{ label: __( 'Date', 'i-downloads' ),           value: 'date' },
							{ label: __( 'Title', 'i-downloads' ),          value: 'title' },
							{ label: __( 'Download count', 'i-downloads' ), value: 'download_count' },
						] }
						onChange={ ( val ) => setAttributes( { orderby: val } ) }
					/>
					<SelectControl
						label={ __( 'Order', 'i-downloads' ) }
						value={ order }
						options={ [
							{ label: __( 'Newest first', 'i-downloads' ),  value: 'DESC' },
							{ label: __( 'Oldest first', 'i-downloads' ), value: 'ASC' },
						] }
						onChange={ ( val ) => setAttributes( { order: val } ) }
					/>
					<ToggleControl
						label={ __( 'Show search bar', 'i-downloads' ) }
						checked={ showSearch }
						onChange={ ( val ) => setAttributes( { showSearch: val } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<Placeholder
					icon="download"
					label={ __( 'Download List', 'i-downloads' ) }
					instructions={ __(
						'Displays a live list of downloads. Configure filters and layout in the sidebar. The preview renders on the frontend.',
						'i-downloads'
					) }
				>
					{ category > 0 && (
						<p style={ { margin: 0, fontSize: '12px', opacity: 0.8 } }>
							{ categoryOptions.find( ( o ) => o.value === category )?.label }
							{ tag > 0 && ' · ' + tagOptions.find( ( o ) => o.value === tag )?.label }
						</p>
					) }
				</Placeholder>
			</div>
		</>
	);
}
