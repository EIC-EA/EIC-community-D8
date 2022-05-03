import React from 'react';
import svg from '../../../../../svg/svg'
import {url} from "../../../../../Services/UrlHelper"

class UserGalleryResultItem extends React.Component {
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

  getUserRole() {
    if(!this.props.currentGroupId) {return 'user'}
    const userId = this.props.result.its_user_id
    if(userId == this.props.groupAdmins.owner) {
      return 'owner'
    } else if (this.props.groupAdmins.admin.includes(userId.toString())) {
      return  'admin'
    }
    return 'user'
  }

  getLastActivity() {
    const lastActivity = this.props.translations.lastActivity || 'Last activity $1'
    const date = new Date(this.props.result.ds_user_access).getTime()
    const now = Date.now()
    const msPerMinute = 60 * 1000;
    const msPerHour = msPerMinute * 60;
    const msPerDay = msPerHour * 24;
    const msPerMonth = msPerDay * 30;
    const elapsed  = now - date
    let time

    if (elapsed < msPerMinute) {
      time = Math.round(elapsed/1000) + ( this.props.translations.seconds || ' seconds ago');
    }
    else if (elapsed < msPerHour) {
      time = Math.round(elapsed/msPerMinute) + ( this.props.translations.minutes || ' minutes ago');
    }
    else if (elapsed < msPerDay ) {
      time = Math.round(elapsed/msPerHour ) + ( this.props.translations.hours || ' hours ago');
    }
    else if (elapsed < msPerMonth) {
      time = Math.round(elapsed/msPerDay) + ( this.props.translations.days || ' days ago');
    }
    else {
      time = Math.round(elapsed/msPerMonth) + ( this.props.translations.months || ' months ago');
    }

    return svg('time')+lastActivity.replace('$1', time)
  }

  render() {
    const userRole = this.getUserRole()
    const roles = {
      owner: `${svg('group_owner')}<span class="ecl-content-type-indicator__label">&nbsp;${    this.props.translations.groupOwner || 'Group owner'}</span>`,
      admin: `${svg('group_administrator')}<span class="ecl-content-type-indicator__label">&nbsp;${    this.props.translations.groupOwner || 'Group administrator' }</span>`,
      user: `<span class="ecl-content-type-indicator__label">&nbsp;</span>`
    }
    if (!this.props.result) {
      return
    }

    const itemUrl = url(this.props.result.ss_url)
    return (
      <div className="ecl-teaser-overview__item " key={this.props.result.tm_global_title}>
        <div className="ecl-teaser ecl-teaser--member ecl-teaser--as-card ecl-teaser--as-card-grey">
          <div className="ecl-teaser__main-wrapper">
            <div className="ecl-teaser__meta-header" aria-hidden="true">
              <div
                className="ecl-content-type-indicator ecl-teaser__type"
                dangerouslySetInnerHTML={{__html: roles[userRole] }}
              />
            </div>
            <a href={itemUrl}>
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
                <a href={itemUrl}>
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
                {0 !== parseInt(this.props.result.ss_user_allow_contact) &&
                <div className="ecl-teaser__action">
                  <a href={`${this.props.result.ss_user_link_contact}?destination=${window.location.pathname}`}
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
              <div
                className="ecl-teaser__updateTime ecl-teaser__updateTime--members"
                dangerouslySetInnerHTML={{__html: this.getLastActivity()}}
              />
            </div>
          </div>
        </div>

      </div>
    );
  }
}

export default UserGalleryResultItem;
