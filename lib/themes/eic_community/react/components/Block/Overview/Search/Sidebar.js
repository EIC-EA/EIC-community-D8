import React from 'react';
import SearchField from "./Field/SearchField";
import CheckboxField from "./Field/CheckboxField";
import CheckboxSpecialField from "./Field/CheckboxSpecialField";
import DateField from "./Field/DateField";
import AddLibraryContent from "./Library/AddLibraryContent";
import CollapsedOptions from "./Field/CollapsedOptions";

class Sidebar extends React.Component {
  constructor(props) {
    super(props);

    this.sourceBundle = window.drupalSettings.overview.source_bundle_id;
    this.translations = window.drupalSettings.translations;
  }

  render() {
    const interestsValue = this.props.facetsValue['interests'];
    let suggestion = '';

    if (Object.keys(this.props.suggestions).length !== 0) {
      suggestion = this.props.suggestions[0][1];
    }

    return (
      <div className="ecl-base-layout__aside">
        {'library' === this.props.bundle &&
        <AddLibraryContent translations={this.props.translations} currentGroupUrl={this.props.currentGroupUrl}/>}
        <div className="ecl-filter-sidebar ecl-filter-sidebar--is-ready">
          <div className="ecl-filter-sidebar__main">
            <h2 className="ecl-filter-sidebar__title">
              {this.props.translations.filter}
            </h2>
            <div className="ecl-filter-sidebar__items">
              {parseInt(this.props.enableFacetInterests) !== 0 &&
              <CheckboxSpecialField label={'My interests only'} checked={interestsValue && interestsValue.my_interests}
                                    key={'my_interests'} facet={'interests'} updateFacet={this.props.updateFacet}
                                    value={'my_interests'}/>}
              {parseInt(this.props.enableFacetMyGroups) !== 0 &&
              <CheckboxSpecialField label={'My groups & content only'}
                                    checked={interestsValue && interestsValue.my_groups} key={'my_groups'}
                                    facet={'interests'} updateFacet={this.props.updateFacet} value={'my_groups'}/>}

              {
                this.props.enableSearch &&
              <SearchField searchText={this.props.searchText} translations={this.props.translations}
                           updateSearchText={this.props.updateSearchText}/>
              }

              {
                this.props.enableDateFilter &&
                <DateField dateRange={this.props.dateRange} translations={this.props.translations} updateDateRange={this.props.updateDateRange} handleCalendarClose={this.props.handleCalendarClose} />
              }

              {suggestion.length !== 0 &&
              <div className="ecl-searchform__suggestion"><p>Did you mean <a
                onClick={() => this.props.updateSearchText(suggestion)}>{suggestion}</a></p></div>
              }

              {
                this.props.facets && Object.keys(this.props.facets).length !== 0 &&
                Object.entries(this.props.facets).map((values, index) => (
                  <CollapsedOptions key={index} values={values} facetsValue={this.props.facetsValue} bundle={this.props.bundle} updateFacet={this.props.updateFacet} />
                ))
              }
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default Sidebar;
