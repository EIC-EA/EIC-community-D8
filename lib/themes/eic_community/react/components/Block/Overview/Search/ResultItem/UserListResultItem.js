import React from 'react';
const svg = require('../../../../../svg/svg')

class UserListResultItem extends React.Component {
  constructor(props) {
    super(props);

    const socials = this.props.result.ss_profile_socials ? JSON.parse(this.props.result.ss_profile_socials) : [];
    let formattedSocials = {};

    socials.map((item) => {
      formattedSocials[item.social] = item.link;
    });

    this.state = {
      socials: formattedSocials,
    };
  }

  render() {
    if (!this.props.result) {
      return
    }

    return (
      <div className="ecl-teaser-overview__item " key={this.props.result.tm_global_title}>
        <div className="ecl-teaser ecl-teaser--member ecl-teaser--as-card ecl-teaser--as-card-grey">
          <div className="ecl-teaser__main-wrapper">
            <div className="ecl-teaser__meta-header ecl-teaser__meta-header--is-hidden" aria-hidden="true">
              <div className="ecl-content-type-indicator ecl-teaser__type">
                <svg className="ecl-icon ecl-icon--s ecl-content-type-indicator__icon" focusable="false"
                     aria-hidden="true">
                </svg>
                <span className="ecl-content-type-indicator__label">&nbsp;</span>
              </div>
            </div>
            <a href={this.props.result.ss_url}>
              {this.props.result.ss_user_profile_image
                ?
                <figure className="ecl-teaser__image-wrapper">
                  <img className="ecl-teaser__image" src={this.props.result.ss_user_profile_image} alt=""/>
                </figure>
                :
                <figure
                  className="ecl-teaser__image-wrapper ecl-teaser__image-wrapper--no-image"
                  dangerouslySetInnerHTML={{__html: svg('user', 'ecl-icon--m')}}
                />
              }
            </a>

            <div className="ecl-teaser__content">
              <h2 className="ecl-teaser__title">
                <a href={this.props.result.ss_url}>

                  <span
                    className="ecl-teaser__title-overflow"><span>{`${this.props.result.ss_global_fullname}`}</span></span>

                </a>
              </h2>

              <p className="ecl-teaser__description">{this.props.result.sm_user_profile_job_string}</p>
              <div className="ecl-teaser__links">
                <a className="ecl-teaser__link" href="?organisation=boxface_inc">Boxface Inc.</a>
              </div>

              {this.props.result.ss_user_profile_city_string && this.props.result.ss_user_profile_field_location_address_country_code &&
              <address className="ecl-teaser__description">{this.props.result.ss_user_profile_city_string}, {this.props.result.ss_user_profile_field_location_address_country_code}</address>
              }
            </div>

            <div className="ecl-teaser__meta-footer">
              <div className="ecl-teaser__actions">
                {this.props.result.ss_user_mail &&
                <div className="ecl-teaser__action">
                  <a href={`mailto:${this.props.result.ss_user_mail}`}
                     className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-before ecl-link--action ecl-teaser__action-icon ecl-teaser__action-icon-mail">
                    <div dangerouslySetInnerHTML={{__html: svg('mail', 'ecl-icon--m')}} />
                    &nbsp;<span className="ecl-link__label">Contact by E-mail</span></a></div>
                }
                {this.state.socials && this.state.socials.facebook &&
                <div className="ecl-teaser__action">
                  <a href={`https://facebook.com/${this.state.socials.facebook}`}
                     className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-before ecl-link--action ecl-teaser__action-icon ecl-teaser__action-icon-facebook">
                    <div dangerouslySetInnerHTML={{__html: svg('facebook', 'ecl-icon--m')}} />
                    &nbsp;<span className="ecl-link__label">View facebook profile</span></a></div>
                }
                {this.state.socials && this.state.socials.twitter &&
                <div className="ecl-teaser__action">
                  <a href={`https://twitter.com/${this.state.socials.twitter}`}
                     className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-before ecl-link--action ecl-teaser__action-icon ecl-teaser__action-icon-twitter">
                    <div dangerouslySetInnerHTML={{__html: svg('twitter', 'ecl-icon--m')}}/>
                    &nbsp;<span className="ecl-link__label">View Twitter profile</span></a></div>
                }
                {this.state.socials && this.state.socials.linkedin &&
                <div className="ecl-teaser__action">
                  <a href={`https://www.linkedin.com/in/${this.state.socials.linkedin}`}
                     className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-before ecl-link--action ecl-teaser__action-icon ecl-teaser__action-icon-linkedin">
                    <div dangerouslySetInnerHTML={{__html: svg('linkedin', 'ecl-icon--m')}}/>
                    &nbsp;<span className="ecl-link__label">View LinkedIn page</span></a></div>
                }
              </div>
            </div>
          </div>
        </div>

      </div>
    );
  }
}

export default UserListResultItem;
