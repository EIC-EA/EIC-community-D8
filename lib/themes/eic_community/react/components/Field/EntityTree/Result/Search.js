import React from "react";
import TextField from '@material-ui/core/TextField';
import Autocomplete from '@material-ui/lab/Autocomplete';
import axios from "axios";
import svg from "../../../../svg/svg";

class Search extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      suggestions: {},
      searchText: ''
    }

    this.onChange = this.onChange.bind(this);
    this.onChangeAutocomplete = this.onChangeAutocomplete.bind(this);
    this.search = this.search.bind(this);
    this.searchTerms = this.searchTerms.bind(this);
    this.searchUsers = this.searchUsers.bind(this);
  }

  onChange(e) {
    const value = e.target.value;
    this.setState({
      searchText: value
    }, this.search)
  }

  onChangeAutocomplete(e, reason) {
    this.props.addChip(reason[1]);

    this.setState({
      searchText: ''
    })
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

    axios.get(this.props.url, {
      params,
      withCredentials: true,
    })
      .then(function (response) {
        self.setState({
          suggestions: response.data,
        })
      })
      .catch(function (error) {
        console.log(error);
      })
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
      current_group: this.props.group
    };

    axios.get(this.props.url, {
      params,
      withCredentials: true,
    })
      .then(function (response) {
        const results = [];

        for (const [key, value] of Object.entries(response.data.response.docs)) {
          results.push({
            'tid': value.its_user_id,
            'parent': -1,
            'name': value.ss_global_fullname,
          });
        }
        self.setState({
          suggestions: results,
        })
      })
      .catch(function (error) {
        console.log(error);
      })
  }

  search() {
    if (this.props.searchSpecificUsers) {
      this.searchUsers();
    } else {
      this.searchTerms();
    }
  }

  render() {
    return <React.Fragment>
      <Autocomplete
        className={this.props.extraClasses}
        freeSolo
        id="entities-tree--search"
        disableClearable
        onChange={this.onChangeAutocomplete}
        options={this.state.searchText ? Object.entries(this.state.suggestions).map((option) => option) : []}
        getOptionLabel={(option) => option[1].name}
        renderInput={(params) => {
          params.inputProps.value = this.state.searchText;
          return <>
            <TextField
            {...params}
            placeholder={this.props.translations.search}
            margin="normal"
            value={this.state.searchText}
            onChange={this.onChange}
          />
            <div className="ecl-select__icon" dangerouslySetInnerHTML={{__html: svg('search', 'ecl-icon ecl-icon--s ecl-select__icon-shape')}} />
          </>
        }
        }
      />
    </React.Fragment>;
  }

}

export default Search;
