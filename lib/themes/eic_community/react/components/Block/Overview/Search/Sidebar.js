import React from 'react';
import SearchField from "./Field/SearchField";
import CheckboxSpecialField from "./Field/CheckboxSpecialField";
import DateField from "./Field/DateField";
import AddLibraryContent from "./Library/AddLibraryContent";
import CollapsedOptions from "./Field/CollapsedOptions";
import svg from '../../../../svg/svg';

class Sidebar extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      expanded: false
    }

    this.sourceBundle = window.drupalSettings.overview.source_bundle_id;
    this.translations = window.drupalSettings.translations;
  }

  render() {
    const interestsValue = this.props.facetsValue['interests'];
    const registrationValue = this.props.facetsValue['filter_registration'];
    let suggestion = '';

    if (Object.keys(this.props.suggestions).length !== 0) {
      suggestion = this.props.suggestions[0][1];
    }

    return (
      <div className="ecl-base-layout__aside">
        {!this.props.isAnonymous && this.props.postContentActions.length > 0 &&
        <AddLibraryContent translations={this.props.translations}
                           currentGroupUrl={this.props.currentGroupUrl}
                           postContentActions={this.props.postContentActions}/>}
        <div aria-expanded={this.state.expended} className="ecl-filter-sidebar ecl-filter-sidebar--is-ready">
          <div className="ecl-filter-sidebar__main">
            <h2 className="ecl-filter-sidebar__title">
              {this.props.isSearchOverview ? this.props.translations.refine : this.props.translations.filter}
            </h2>
            <div className="ecl-filter-sidebar__items">
              {parseInt(this.props.enableFacetInterests) !== 0 &&
              <CheckboxSpecialField label={'My interests only'}
                                    checked={interestsValue && interestsValue.my_interests}
                                    key={'my_interests'} facet={'interests'}
                                    updateFacet={this.props.updateFacet}
                                    value={'my_interests'}/>}
              {parseInt(this.props.enableFacetMyGroups) !== 0 &&
              <CheckboxSpecialField label={window.drupalSettings.overview.label_my_groups}
                                    checked={interestsValue && interestsValue.my_groups} key={'my_groups'}
                                    facet={'interests'} updateFacet={this.props.updateFacet}
                                    value={'my_groups'}/>}

              {
                this.props.enableSearch &&
                <SearchField
                  bundle={this.props.bundle}
                  showLabel={true}
                  searchText={this.props.searchText}
                  translations={this.props.translations}
                  updateSearchText={this.props.updateSearchText}
                />
              }

              {
                this.props.enableDateFilter &&
                <DateField dateRange={this.props.dateRange} translations={this.props.translations}
                           updateDateRange={this.props.updateDateRange}
                           handleCalendarClose={this.props.handleCalendarClose}/>
              }

              {
                this.props.enableRegistrationFilter &&
                <CheckboxSpecialField label={window.drupalSettings.overview.open_registration_filter}
                                      checked={registrationValue && registrationValue.open_registration} key={'filter_registration'}
                                      facet={'filter_registration'} updateFacet={this.props.updateFacet}
                                      value={'open_registration'}/>
              }

              {
                this.props.enableInviteUserAction &&
                <div className={'ecl-filter-sidebar__item'}>
                  <a href={this.props.inviteUserUrl}
                     className={'ecl-link ecl-link--default ecl-link--button ecl-link--button-primary'}>
                    {this.props.translations.invite_member}
                  </a>
                </div>
              }

              {suggestion.length !== 0 &&
              <div className="ecl-searchform__suggestion"><p>Did you mean <a
                href='#'
                onClick={() => this.props.updateSearchText(suggestion)}>{suggestion}</a></p></div>
              }

              {
                this.props.facets && Object.keys(this.props.facets).length !== 0 &&
                Object.entries(this.props.facets).map((values, index) => (
                  <CollapsedOptions
                    translations={this.props.translations}
                    key={index} values={values}
                    facetsValue={this.props.facetsValue}
                    bundle={this.props.bundle}
                    updateFacet={this.props.updateFacet}
                  />
                ))
              }
            </div>
          </div>
          <div className="ecl-filter-sidebar__splash">
            <button onClick={() => this.setState({expended: !this.state.expended})}
                    className="ecl-button ecl-button--primary ecl-filter-sidebar__expand" type="submit">
              <span className="ecl-button__container"
                    dangerouslySetInnerHTML={
                      {__html: svg('arrow', 'ecl-icon ecl-icon--xs ecl-button__icon ecl-button__icon--before') + '<span className="ecl-button__label" data-ecl-label="true">Show filters</span>'}
                    }/>
            </button>
            <button onClick={() => this.setState({expended: !this.state.expended})}
                    className="ecl-button ecl-button--primary ecl-filter-sidebar__collapse" type="submit">
              <span className="ecl-button__container"
                    dangerouslySetInnerHTML={
                      {__html: svg('arrow-down', 'ecl-icon ecl-icon--xs ecl-button__icon ecl-button__icon--before') + '<span className="ecl-button__label" data-ecl-label="true">Hide filters</span>'}
                    }/>
            </button>
          </div>
        </div>
      </div>
    );
  }

}

export default Sidebar;
