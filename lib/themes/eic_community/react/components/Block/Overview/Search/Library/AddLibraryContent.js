import React from 'react';
import initCollapse from "../../../../../Services/CollapseOptions";
const svg = require('../../../../../svg/svg');

class AddLibraryContent extends React.Component {
  constructor(props) {
    super(props);
  }

  componentDidMount() {
    initCollapse();
  }

  render() {
    const uri = window.location.href.replace(window.location.origin, '');
    const btnContent = `${ svg('plus', 'ecl-icon ecl-icon--s ecl-button__icon ecl-button__icon--before')}<span
                className="ecl-button__label" data-ecl-label="true">${this.props.translations.post_content}</span></span>
              </button> `

    return (
      <div className="ecl-editorial-header__actions">
        <div className="ecl-editorial-header__action">
          {this.props.postContentActions.length > 1 ?
            <div
              className="ecl-collapsible-options ecl-collapsible-options--aligns-from-right ecl-collapsible-options--actions">
              <div className="ecl-collapsible-options__trigger-wrapper">
                <button className="ecl-button ecl-button--call ecl-collapsible-options__trigger" type="submit">
                  <span className="ecl-button__container" tabIndex="-1" dangerouslySetInnerHTML={{__html:btnContent}} />
                </button>
              </div>

              <div className="ecl-collapsible-options__collapse-wrapper">
                <div className="ecl-collapsible-options__collapse-well">
                  <div className="ecl-collapsible-options__items">
                    {this.props.postContentActions.map(value => {
                      return <div className="ecl-collapsible-options__item">
                        <a href={value.path}
                          className="ecl-link ecl-link--default ecl-link--icon ecl-link--icon-before ecl-link--button ecl-link--button-ghost ecl-collapsible-options__button ">
                          &nbsp;<span className="ecl-link__label">{value.label}</span></a>
                      </div>;
                    })}
                  </div>
                </div>
              </div>
            </div> :
            this.props.postContentActions.map(value => {
              return <a href={value.path}
                   className="ecl-link ecl-link--default ecl-link ecl-link--cta">
                  &nbsp;<span className="ecl-link__label">{value.label}</span></a>;
            })
          }
        </div>
      </div>
    );
  }
}

export default AddLibraryContent;
