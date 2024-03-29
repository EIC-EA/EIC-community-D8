import React from 'react';
import TextField from '@material-ui/core/TextField';
import Autocomplete, { createFilterOptions } from '@material-ui/lab/Autocomplete';
import axios from 'axios';
import svg from '../../../../svg/svg';

const filter = createFilterOptions();

class Search extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      suggestions: {},
      searchText: '',
      error: '',
    };

    this.onChange = this.onChange.bind(this);
    this.onChangeAutocomplete = this.onChangeAutocomplete.bind(this);
    this.search = this.search.bind(this);
    this.searchTerms = this.searchTerms.bind(this);
    this.searchUsers = this.searchUsers.bind(this);
    this.addTerms = this.addTerms.bind(this);
  }

  onChange(e) {
    if(e === null) {
      return
    }
    this.setState(
      {
        searchText:  e.target.value,
      },
      this.search
    );
  }

  onChangeAutocomplete(e, reason) {
    if (reason == null) {
      this.setState({
        searchText: '',
      });
      return;
    }

    if(e.key === 'Enter') {
      const suggestions = this.state.suggestions
      const suggestionsKeys = Object.keys(suggestions)
      reason = suggestionsKeys.length > 0 
      ? suggestions[suggestionsKeys[0]] 
      : {
          inputValue: reason,
          name: reason
        }
    }

    if (reason.inputValue && this.props.canCreateTag) {
      this.addTerms(reason);
    } else {
      this.props.addChip(reason);
    }

    this.setState({
      searchText: '',
    });
  }

  addTerms(term) {
    const self = this;
    axios
      .post(this.props.createTermUrl, {
        target_bundle: this.props.targetBundle,
        name: term.inputValue,
        withCredentials: true,
      })
      .then(function (response) {
        if (response.data.error == 0) {
          self.props.addChip(response.data.result);
        } else {
          self.setState({ error: response.data.message });
        }
      })
      .catch(function (error) {
        console.log(error);
      });
  }

  searchTerms() {
    const self = this;
    const params = {
      search_text: this.state.searchText,
      values: this.props.values,
      targetEntity: this.props.targetEntity,
      targetBundle: this.props.targetBundle,
      disableTop: this.props.disableTop,
      page: this.props.page,
    };

    axios
      .get(this.props.url, {
        params,
        withCredentials: true,
      })
      .then(function (response) {
        self.setState({
          suggestions: response.data,
        });
      })
      .catch(function (error) {
        console.log(error);
      });
  }

  searchUsers() {
    const self = this;
    const params = {
      search_value: this.state.searchText,
      values: this.props.values,
      targetEntity: this.props.targetEntity,
      targetBundle: this.props.targetBundle,
      disableTop: this.props.disableTop,
      page: this.props.page,
      current_group: this.props.group,
    };

    axios
      .get(this.props.url, {
        params,
        withCredentials: true,
      })
      .then(function (response) {
        const results = [];

        for (const [key, value] of Object.entries(response.data.response.docs)) {
          results.push({
            tid: value.its_user_id,
            parent: -1,
            name: value.ss_global_fullname,
          });
        }
        self.setState({
          suggestions: results,
        });
      })
      .catch(function (error) {
        console.log(error);
      });
  }

  search() {
    if (this.props.searchSpecificUsers) {
      this.searchUsers();
    } else {
      this.searchTerms();
    }
  }

  render() {
    return (
      <React.Fragment>
        {this.state.error && this.state.error}
        <Autocomplete
          freeSolo
          className={this.props.extraClasses}
          id="entities-tree--search"
          onChange={this.onChangeAutocomplete}
          onInputChange={this.onChange}
          value={this.state.searchText}
          onKeyDown={(e) => {if(e.key === 'Enter') {this.onChangeAutocomplete(e, this.state.searchText)}}}
          options={
            this.state.searchText
              ? Object.entries(this.state.suggestions).map((option) => option[1])
              : []
          }
          getOptionLabel={(option) => {
            if (typeof option === 'string') {
              return option;
            }
            if (option.inputValue) {
              return option.inputValue;
            }
            return option.name;
          }}
          filterOptions={(options, params) => {
            params.inputValue = this.state.searchText
            const suggestions = Object.values(this.state.suggestions).map((value) => value.name)
            const filtered = filter(options, params);
            if (params.inputValue !== '' && this.props.canCreateTag && !suggestions.includes(params.inputValue)) {
              filtered.push({
                inputValue: params.inputValue,
                name: `${this.props.translations.add_label || 'Add'} "${params.inputValue}"`,
              });
            }
            return filtered;
          }}
          renderOption={(option) => option.name}
          renderInput={(params) => {
            return (
              <>
                <TextField
                  {...params}
                  placeholder={this.props.translations.search}
                  margin="normal"
                />
                <div
                  className="ecl-select__icon"
                  dangerouslySetInnerHTML={{
                    __html: svg('search', 'ecl-icon ecl-icon--s ecl-select__icon-shape'),
                  }}
                />
              </>
            );
          }}
        />
      </React.Fragment>
    );
  }
}

export default Search;
