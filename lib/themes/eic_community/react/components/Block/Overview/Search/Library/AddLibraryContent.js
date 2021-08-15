import React from 'react';

class AddLibraryContent extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const uri = window.location.href.replace(window.location.origin, '');

    return (
      <div className="ecl-editorial-header__actions">
        <div className="ecl-editorial-header__action">
          <div className="ecl-collapsible-options ecl-collapsible-options--aligns-from-right">
            <div className="ecl-collapsible-options__trigger-wrapper">
              <button className="ecl-button ecl-button--call ecl-collapsible-options__trigger" type="submit">
                {this.props.translations.post_content}
              </button>
            </div>

            <div className="ecl-collapsible-options__collapse-wrapper">
              <div className="ecl-collapsible-options__collapse-well">
                <div className="ecl-collapsible-options__items">
                  <div className="ecl-collapsible-options__item">
                    <a
                      href={`${this.props.currentGroupUrl}/content/create/group_node%3Adocument?destination=${uri}`}
                      className="ecl-link ecl-link--default ecl-link--button ecl-link--button-ghost ecl-collapsible-options__button "
                    >
                      {this.props.translations.add_document}
                    </a>
                  </div>
                  <div className="ecl-collapsible-options__item">
                    <a
                      href={`${this.props.currentGroupUrl}/content/create/group_node%3Agallery?destination=${uri}`}
                      className="ecl-link ecl-link--default ecl-link--button ecl-link--button-ghost ecl-collapsible-options__button "
                    >
                      {this.props.translations.add_gallery}
                    </a>
                  </div>
                  <div className="ecl-collapsible-options__item">
                    <a
                      href={`${this.props.currentGroupUrl}/content/create/group_node%3Avideo?destination=${uri}`}
                      className="ecl-link ecl-link--default ecl-link--button ecl-link--button-ghost ecl-collapsible-options__button "
                    >
                      {this.props.translations.add_video}
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    );
  }
}

export default AddLibraryContent;
