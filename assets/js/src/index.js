/**
 * Internal dependencies
 */
import RadioTermSelector from './radio-term-selector';
import OptionTermSelector from './option-term-selector';

function CustomizeTaxonomySelector(OriginalComponent) {
    return function(props) {
        // props.slug is the taxonomy (slug)
        if (RB4Tl18n.radio_taxonomies.indexOf(props.slug) >= 0) {

            return wp.element.createElement(
                RadioTermSelector,
                props
            );
        } else if (RB4Tl18n.option_taxonomies.indexOf(props.slug) >= 0) {

            return wp.element.createElement(
                OptionTermSelector,
                props
            );
        } else {
            return wp.element.createElement(
                OriginalComponent,
                props
            );
        }
    }
};

wp.hooks.addFilter(
    'editor.PostTaxonomyType',
    'RB4T',
    CustomizeTaxonomySelector
);