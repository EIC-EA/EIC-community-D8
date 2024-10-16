import React from 'react';

const svg = require('../../../../../svg/svg');
import {timeDifferenceFromNow} from '../../../../../Services/TimeHelper';
import UserImage from '../../../../Utils/UserImage';
import {url} from "../../../../../Services/UrlHelper";

class GroupProjectResultItem extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    if (!this.props.result) {
      return;
    }

    const startDate = new Date(this.props.result?.ds_group_field_project_date)
    const endDate = new Date(this.props.result?.ds_group_field_project_date_end_value)
    const startDateDay = startDate.toLocaleString('default', {day: '2-digit', timeZone: 'CET'})
    const startDateMonth = startDate.toLocaleString('default', {month: 'short', timeZone: 'CET'})
    const startDateYear = startDate.toLocaleString('default', {year: 'numeric', timeZone: 'CET'})
    const endDateDay = endDate.toLocaleString('default', {day: '2-digit', timeZone: 'CET'})
    const endDateMonth = endDate.toLocaleString('default', {month: 'short', timeZone: 'CET'})
    const endDateYear = endDate.toLocaleString('default', {year: 'numeric', timeZone: 'CET'})
    const moderationState = this.props.result.ss_global_last_moderation_state;
    const itemUrl = url(this.props.result.ss_url);
    const projectHorizonIcon = `<use xlink:href="${url('themes/custom/eic_community/dist/images/sprite/custom/sprites/custom.svg#general--growth')}"></use>`;
    const projectInnovationIcon = `<use xlink:href="${url('themes/custom/eic_community/dist/images/sprite/custom/sprites/custom.svg#custom--check_circle')}"></use>`;
    const projectCordisIcon = `<use xlink:href="${url('themes/custom/eic_community/dist/images/sprite/custom/sprites/custom.svg#general--spreadsheet')}"></use>`;
    const projectWebsiteIcon = `<use xlink:href="${url('themes/custom/eic_community/dist/images/sprite/custom/sprites/custom.svg#custom--star_circle')}"></use>`;
    const projectExternalLinkIcon = `<use xlink:href="${url('themes/custom/eic_community/dist/images/sprite/custom/sprites/custom.svg#custom--external_link')}"></use>`;

    let tags = !this.props.isAnonymous ?
      [
        {
          label: this.props.result.ss_group_visibility_label,
          id: this.props.result.ss_group_visibility_label && this.props.result.ss_group_visibility_label.toLowerCase(),
        },
      ] : [];

    if (moderationState !== 'published') {
      let moderationId = 'public';
      switch (moderationState) {
        case 'draft':
        case 'blocked':
          moderationId = 'private';
          break;
        case 'pending':
          moderationId = 'restricted';
          break;
      }

      tags = [...tags, {
        label: moderationState.charAt(0).toUpperCase() + moderationState.slice(1),
        id: moderationId,
      }];
    }

    return (
      <div className="ecl-teaser-overview__item" key={this.props.result.tm_global_title}>
        <div className="ecl-teaser ecl-teaser--group ecl-teaser--project">
          <div className="ecl-teaser__main-wrapper">
            <div className="ecl-teaser__content-wrapper">
              <div className="ecl-teaser__content">
                <h2 className="ecl-teaser__title">
                  <a href={itemUrl}>
                    <span className="ecl-teaser__title-overflow">
                      <span>{this.props.result.tm_global_title}</span>
                    </span>
                  </a>
                </h2>
                <div className="ecl-teaser__meta-header">
                  {this.props.result.its_project_grant_agreement_id && (
                    <span className="ecl-u-mr-l"><strong>ID: </strong>{this.props.result.its_project_grant_agreement_id}</span>
                  )}
                  {(this.props.result.ds_group_field_project_date && this.props.result.ds_group_field_project_date_end_value) && (
                    <span>
                      <strong>From&nbsp;</strong>{startDateDay} {startDateMonth} {startDateYear}<strong>&nbsp;to&nbsp;</strong>{endDateDay} {endDateMonth} {endDateYear}
                    </span>
                  )}
                </div>
                {(this.props.result.tm_X3b_en_project_teaser) && (
                  <div className="ecl-teaser__body">{this.props.result.tm_X3b_en_project_teaser}</div>
                )}
                <div className="ecl-teaser__meta-footer">
                {this.props.result.ss_group_project_participating_countries !== undefined && (JSON.parse(this.props.result.ss_group_project_participating_countries).coordinators.length !==0 || JSON.parse(this.props.result?.ss_group_project_participating_countries).participants.length !==0) &&
                  <div className="ecl-teaser__participating-countries">
                    <strong>Participating countries:</strong>
                      <div className="ecl-teaser__participating-countries-items">
                        {JSON.parse(this.props.result.ss_group_project_participating_countries).coordinators.map((item, index) => (
                          <div key={`coordinator-${index}`} className="ecl-u-d-inline-flex ecl-u-ml-2xs ecl-u-align-items-center">
                            <img
                              src={url(`themes/custom/eic_community/dist/images//flags/24/${item.country_code}.png`)}
                              alt={`Icon for ${item.name}`}
                            />
                            <div className="ecl-u-ml-2xs">{item.name} (Coordinator)</div>
                          </div>
                        ))}
                        {JSON.parse(this.props.result.ss_group_project_participating_countries).participants.map((item, index) => (
                          <div key={`participant-${index}`} className="ecl-u-d-inline-flex ecl-u-ml-2xs ecl-u-align-items-center">
                            <img
                              src={url(`themes/custom/eic_community/dist/images//flags/24/${item.country_code}.png`)}
                              alt={`Icon for ${item.name}`}
                            />
                            <div className="ecl-u-ml-2xs">{item.name}</div>
                          </div>
                        ))}
                      </div>
                    </div>
                  }
                  {this.props.result.ss_group_project_funding_programme && (
                    <div className="ecl-teaser__programme">
                      <strong>Programme: </strong><span>{this.props.result.ss_group_project_funding_programme}</span>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
          <div className="ecl-teaser__sidebar">
            {this.props.result.ss_group_project_fields_of_science !== undefined &&
              JSON.parse(this.props.result.ss_group_project_fields_of_science).map((item) => (
                <div key={item.machine_readable_name} className={`ecl-field-of-science ecl-field-of-science--${item.machine_readable_name}`}>
                  <div className="ecl-field-of-science__icon">
                    <img
                      src={url(`themes/custom/eic_community/dist/images/fields-of-science/${item.machine_readable_name}.svg`)}
                      alt={`SVG for ${item.name}`}
                    />
                  </div>
                  <div className="ecl-field-of-science__label">{item.name}</div>
                </div>
              ))
            }

            {this.props.result.ss_field_project_horizon_results !== undefined &&
              <div className="ecl-u-mb-s">
                <a href={this.props.result.ss_field_project_horizon_results}
                   className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-before ecl-link--button ecl-link--button-primary ecl-u-width-100 ">
                  <svg className="ecl-icon ecl-icon--m ecl-link__icon" focusable="false" aria-hidden="true"
                       dangerouslySetInnerHTML={{__html: projectHorizonIcon}}/>
                  &nbsp;
                  <span className="ecl-link__label">view Horizon results</span>
                  <svg className="ecl-icon ecl-icon--m ecl-link__icon ecl-link--external" focusable="false"
                       aria-hidden="true" dangerouslySetInnerHTML={{__html: projectExternalLinkIcon}}/>
                </a>
              </div>
            }
            {this.props.result.ss_project_innovations !== undefined &&
              <div className="ecl-u-mb-s">
                <a href={this.props.result.ss_project_innovations}
                   className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-before ecl-link--button ecl-link--button-primary ecl-u-width-100 ">
                  <svg className="ecl-icon ecl-icon--m ecl-link__icon" focusable="false" aria-hidden="true"
                       dangerouslySetInnerHTML={{__html: projectInnovationIcon}}/>
                  &nbsp;
                  <span className="ecl-link__label">view on InnoRadar</span>
                  <svg className="ecl-icon ecl-icon--m ecl-link__icon ecl-link--external" focusable="false"
                       aria-hidden="true" dangerouslySetInnerHTML={{__html: projectExternalLinkIcon}}/>
                </a>
              </div>
            }
            {this.props.result.ss_project_cordis_url !== undefined &&
              <div className="ecl-u-mb-s">
                <a href={this.props.result.ss_project_cordis_url}
                   className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-before ecl-link--button ecl-link--button-primary ecl-u-width-100 ">
                  <svg className="ecl-icon ecl-icon--m ecl-link__icon" focusable="false" aria-hidden="true"
                       dangerouslySetInnerHTML={{__html: projectCordisIcon}}/>
                  &nbsp;
                  <span className="ecl-link__label">view on CORDIS</span>
                  <svg className="ecl-icon ecl-icon--m ecl-link__icon ecl-link--external" focusable="false"
                       aria-hidden="true" dangerouslySetInnerHTML={{__html: projectExternalLinkIcon}}/>
                </a>
              </div>
            }
            {this.props.result.ss_project_website !== undefined &&
              <div className="ecl-u-mb-s">
                <a href={this.props.result.ss_project_website}
                   className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-before ecl-link--button ecl-link--button-primary ecl-u-width-100 ">
                  <svg className="ecl-icon ecl-icon--m ecl-link__icon" focusable="false" aria-hidden="true"
                       dangerouslySetInnerHTML={{__html: projectWebsiteIcon}}/>
                  &nbsp;
                  <span className="ecl-link__label">view project website</span>
                  <svg className="ecl-icon ecl-icon--m ecl-link__icon ecl-link--external" focusable="false"
                       aria-hidden="true" dangerouslySetInnerHTML={{__html: projectExternalLinkIcon}}/>
                </a>
              </div>
            }
          </div>
        </div>
      </div>
    );
  }
}
export default GroupProjectResultItem;
