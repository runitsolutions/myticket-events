const { __ } = wp.i18n; // Import __() from wp.i18n
const { Component } = wp.element;
const { serverSideRender: ServerSideRender } = wp;
const { InspectorControls, PanelColorSettings } = wp.editor;
const { RangeControl, CheckboxControl, RadioControl, PanelBody, TextControl, TextareaControl, ExternalLink, PanelRow } = wp.components;

import { InspectorContainer } from '../commonComponents/container/container';

/**
 * The edit function describes the structure of your block in the context of the editor.
 * This represents what the editor will render when the block is used.
 *
 * The "edit" property must be a valid function.
 * @param {Object} props - attributes
 * @returns {Node} rendered component
 */
export default class Edit extends Component {

    render() {
        const {
            className,
            attributes,
            setAttributes,
        } = this.props;

        return (
            <div className={ className }>
                <InspectorControls
                    setAttributes={ setAttributes }
                    { ...attributes }
                >
                    <PanelBody
                        title={ __( 'General', 'myticket-events'  ) }
                        initialOpen={ false }
                    >

                        <TextareaControl
                            label={ __( 'Product IDs', 'myticket-events' ) }
                            value={ attributes.eventID }
                            onChange={ (eventID) => setAttributes( { eventID: eventID } ) }
                            help={ __( 'Go to Products > All Products > hover product to view its ID. Provide multiple IDs per each row to allow users pick events for different days.', 'myticket-events' ) }
                        />

                        <TextControl
                            label={ __( 'Tickets per booking', 'myticket-events' ) }
                            type="number"
                            value={ attributes.ticketsPerBooking }
                            onChange={ (value) => setAttributes( { ticketsPerBooking: value } ) }
                            help={ __( 'Maximum amount of tickets one user is allowed to reserve per booking.', 'myticket-events' ) }
                        />
                        		
                        <RadioControl
                            label={ __( 'Render mode', 'myticket-events' ) }
                            selected={ attributes.renderType }
                            className="position"
                            options={ [
                                { label: 'Seats mode', value: '1' },
                                { label: 'Zones mode', value: '0' },
                            ] }
                            onChange={ ( renderType ) => {
                                setAttributes( { renderType } );
                            } }
                            help={ __( 'Draw seats instead of zones when layout is rendered. Recommended for small layouts with less than 500 seats.', 'myticket-events' ) }
                        />
                    </PanelBody>

                    <PanelBody
                        title={ __( 'Style', 'myticket-events'  ) }
                        initialOpen={ false }
                    >

                        <CheckboxControl
                            label={ __( 'Hide seat numbers', 'myticket-events' ) }
                            checked={ attributes.hideNumbers }
                            onChange={ ( hideNumbers ) => {
                                setAttributes( { hideNumbers } );
                            } }
                            help={ __( 'Hide seat numbers. Usefull when hall layout image already has numbers assigned.', 'myticket-events' ) }
                        />

                        <RangeControl
                            label={ __( 'Overlay opacity', 'myticket-events'  ) }
                            value={ attributes.numOpacity }
                            onChange={ ( numOpacity ) => {
                                setAttributes( { numOpacity } );
                            } }
                            min={ 1 }
                            max={ 100 }
                            help={ __( 'Seat and zone opacity level.', 'myticket-events'  ) }
                        />

                        <RangeControl
                            label={ __( 'Zone background opacity', 'myticket-events'  ) }
                            value={ attributes.numOpacity2 }
                            onChange={ ( numOpacity2 ) => {
                                setAttributes( { numOpacity2 } );
                            } }
                            min={ 1 }
                            max={ 100 }
                            help={ __( 'Zone background opacity level. Reduce to peview custom zone background image.', 'myticket-events'  ) }
                        />

                        <RadioControl
                            label={ __( 'Seat Style', 'myticket-events' ) }
                            selected={ attributes.seatMode }
                            options={ [
                                { label: 'Round', value: 'circle' },
                                { label: 'Square', value: 'rect' },
                            ] }
                            onChange={ ( seatMode ) => {
                                setAttributes( { seatMode } );
                            } }
                            help={ __( 'Override default seat style.', 'myticket-events' ) }
                        />

                        <RangeControl
                            label={ __( 'Seat number size', 'myticket-events'  ) }
                            value={ attributes.snSize }
                            onChange={ ( snSize ) => {
                                setAttributes( { snSize } );
                            } }
                            min={ 8 }
                            max={ 24 }
                        />

                    </PanelBody>

                    <PanelBody
                        title={ __( 'Colors', 'myticket-events'  ) }
                        initialOpen={ false }
                    >

                        <PanelColorSettings
                            title={ __( 'Highlight', 'myticket-events'  ) }
                            initialOpen={ true }
                            colorSettings={ [
                                    {
                                        value: attributes.mainColor,
                                        onChange: ( value ) => {
                                            return setAttributes( { mainColor: value } );
                                        },
                                        label: __( 'Selected', 'myticket-events'  ),
                                    },
                                ] }
                        />

                        <PanelColorSettings
                            title={ __( 'Availability', 'myticket-events'  ) }
                            initialOpen={ false }
                            colorSettings={ [
                                    {
                                        value: attributes.availableColor,
                                        onChange: ( value ) => {
                                            return setAttributes( { availableColor: value } );
                                        },
                                        label: __( 'Selected', 'myticket-events'  ),
                                    },
                                ] }
                        />

                        <PanelColorSettings
                            title={ __( 'Sold out', 'myticket-events'  ) }
                            initialOpen={ false }
                            colorSettings={ [
                                    {
                                        value: attributes.soldoutColor,
                                        onChange: ( value ) => {
                                            return setAttributes( { soldoutColor: value } );
                                        },
                                        label: __( 'Selected', 'myticket-events'  ),
                                    },
                                ] }
                        />

                        <PanelColorSettings
                            title={ __( 'Selected', 'myticket-events'  ) }
                            initialOpen={ false }
                            colorSettings={ [
                                    {
                                        value: attributes.selectedColor,
                                        onChange: ( value ) => {
                                            return setAttributes( { selectedColor: value } );
                                        },
                                        label: __( 'Selected', 'myticket-events'  ),
                                    },
                                ] }
                        />
                        
                        <PanelColorSettings
                            title={ __( 'Seat number', 'myticket-events'  ) }
                            initialOpen={ false }
                            colorSettings={ [
                                    {
                                        value: attributes.seatsColor,
                                        onChange: ( value ) => {
                                            return setAttributes( { seatsColor: value } );
                                        },
                                        label: __( 'Selected', 'myticket-events'  ),
                                    },
                                ] }
                        />

                    </PanelBody>

                    <PanelBody
                        title={ __( 'Texts', 'myticket-events'  ) }
                        initialOpen={ false }
                    >

                        <TextControl
                            label={ __( 'Date', 'myticket-events' ) }
                            value={ attributes.title }
                            onChange={ (value) => setAttributes( { title: value } ) }
                            help={ __( 'Leave blank to show date picker.', 'myticket-events' ) }
                        />

                        <TextControl
                            label={ __( 'Title', 'myticket-events' ) }
                            value={ attributes.subtitle }
                            onChange={ (value) => setAttributes( { subtitle: value } ) }
                            help={ __( 'Leave blank to show event title.', 'myticket-events' ) }
                        />

                        <TextControl
                            label={ __( 'Venue', 'myticket-events' ) }
                            value={ attributes.desc }
                            onChange={ (value) => setAttributes( { desc: value } ) }
                            help={ __( 'Leave blank to show event venue.', 'myticket-events' ) }
                        />

                        <TextareaControl
                            label={ __( 'Bottom note', 'myticket-events' ) }
                            value={ attributes.note }
                            onChange={ (value) => setAttributes( { note: value } ) }
                            help={ __( 'Leave blank to hide.', 'myticket-events' ) }
                        />
 
                        <TextControl
                            label={ __( 'Button text', 'myticket-events' ) }
                            value={ attributes.cta }
                            onChange={ (value) => setAttributes( { cta: value } ) }
                        />
                    </PanelBody>

                    <PanelBody
                        title={ __( 'Layout', 'myticket-events' ) }
                        initialOpen={ false }
                    >
                        <TextareaControl
                            label={ __( 'Seat code', 'myticket-events' ) }
                            value={ attributes.filterLocations }
                            onChange={ ( filterLocations ) => setAttributes( { filterLocations } ) }
                            // help={ __( 'Import seat code from MyTicket Events dashboard.', 'myticket-events' ) }
                        />

                        <PanelRow>
                            { __( 'Import seat code from MyTicket Events dashboard.', 'myticket-events' ) } 
                        </PanelRow>

                        <PanelRow>
                            <ExternalLink 
                                href="https://myticket.kenzap.cloud/"  >
                                { __( 'MyTicket Events dashboard', 'myticket-events' ) }
                            </ExternalLink>
                        </PanelRow>

                        <br/>

                        <TextControl
                            label={ __( 'Summary table', 'myticket-events' ) }
                            value={ attributes.tableLayout }
                            onChange={ ( tableLayout ) => setAttributes( { tableLayout } ) }
                            // help={ __( 'To create layout go to https://myticket.kenzap.cloud/', 'myticket-events' ) }
                        />

                        <PanelRow>
                            { __( 'Override default layout summary table located below the seating map.', 'myticket-events' ) } 
                        </PanelRow>

                        <PanelRow>
                            <ExternalLink 
                                href="https://kenzap.blog/overriding-hall-order-summary-table-in-myticket-events-plugin/"  >
                                { __( 'Summary table integration', 'myticket-events' ) }
                            </ExternalLink>
                        </PanelRow>

                        <br/>

                        <CheckboxControl
                            label={ __( 'Arrows', 'myticket-events' ) }
                            checked={ attributes.showArrows }
                            onChange={ ( showArrows ) => {
                                setAttributes( { showArrows } );
                            } }
                            help={ __( 'Show right/left arrows during seat selection.', 'myticket-events' ) }
                        />

                        <TextControl
                            label={ __( 'Max layout width', 'myticket-events' ) }
                            type="number"
                            value={ attributes.dwidth }
                            onChange={ (value) => setAttributes( { dwidth: value } ) }
                            help={ __( 'Maximum width of the layout in desktop mode. In case layout is too wide a scroll can be used to fit the layout.', 'myticket-events' ) }
                        />

                        <TextControl
                            label={ __( 'Min layout width', 'myticket-events' ) }
                            type="number"
                            value={ attributes.mwidth }
                            onChange={ (value) => setAttributes( { mwidth: value } ) }
                            help={ __( 'Minimal width of the layout in mobile mode. In case layout is too wide a scroll can be used to fit the layout.', 'myticket-events' ) }
                        />
                        
                        <TextControl
                            label={ __( 'Max selection width', 'myticket-events' ) }
                            type="number"
                            value={ attributes.smaxwidth }
                            onChange={ (value) => setAttributes( { smaxwidth: value } ) }
                            help={ __( 'Maximum width of layout during ticket selection in desktop mode. Reduce this parameter in order to fit layout with many seats.', 'myticket-events' ) }
                        />

                        <TextControl
                            label={ __( 'Min selection width', 'myticket-events' ) }
                            type="number"
                            value={ attributes.sminwidth }
                            onChange={ (value) => setAttributes( { sminwidth: value } ) }
                            help={ __( 'Minimal width of layout during ticket selection in desktop mode. Increase this parameter in order to fit layout with many seats.', 'myticket-events' ) }
                        />
                        
                    </PanelBody>
                    
                    <InspectorContainer
                        setAttributes={ setAttributes }
                        { ...attributes }
                        withPadding
                        withWidth100
                        withBackground
                    />

                    <PanelBody
                        title={ __( 'Support', 'myticket-events' ) }
                        initialOpen={ false }
                    >

                        <PanelRow>
                            { __( 'Free community support and frequently asked questions:', 'myticket-events' ) } 
                        </PanelRow>

                        <PanelRow>
                            <ExternalLink 
                                href="https://kenzap.com/myticket-events-plugin/"  >
                                { __( 'Community forum', 'myticket-events' ) }
                            </ExternalLink>
                        </PanelRow>

                        <PanelRow>
                            { __( 'Seat mapping integration tutorial:', 'myticket-events' ) } 
                        </PanelRow>

                        <PanelRow>
                            <ExternalLink 
                                href="https://kenzap.blog/how-to-create-custom-concert-hall-in-wordpress-with-seat-reservation/"  >
                                { __( 'Integration tutorial', 'myticket-events' ) }
                            </ExternalLink>
                        </PanelRow>

                        <PanelRow>
                            { __( 'Paid assistance and layout integration support:', 'myticket-events' ) } 
                        </PanelRow>

                        <PanelRow>
                            <ExternalLink 
                                href="https://kenzap.com/myticket-events-plugin-support-customization-service-1016004/"  >
                                { __( 'Customization service', 'myticket-events' ) }
                            </ExternalLink>
                        </PanelRow>

                    </PanelBody>
                </InspectorControls>

                <ServerSideRender
                    block="myticket-events/listing-05"
                    attributes={ {
                        align: attributes.align,
                        serverSide: true,
                    } }
                />
            </div>
        );
    }
}
