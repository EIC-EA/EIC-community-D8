import React, { useEffect, useState } from 'react'
import useAutocomplete from '@material-ui/lab/useAutocomplete';
import axios from 'axios';
import svg from '../../../../svg/svg'

const Autocomplete = (props) => {
    const [searchText, setSearchText] = useState('')
    const [suggestions, setSuggestions] = useState([])
    const [dropdownIsOpen, setDropdownIsOpen] = useState(false)
    const {
        getRootProps,
        getInputLabelProps,
        getInputProps,
        getTagProps,
        getListboxProps,
        getOptionProps,
        groupedOptions,
        value,
    } = useAutocomplete({
        id: 'customized-hook-demo',
        defaultValue: [],
        multiple: true,
        options: [],
        getOptionLabel: (option) => option.name,
    });

    function searchTerms() {
        const params = {
            search_text: searchText,
            values: props.values,
            targetEntity: props.targetEntity,
            targetBundle: props.targetBundle,
            disableTop: props.disableTop,
            page: props.page,
        };

        axios.get(props.url, {
            params,
            withCredentials: true,
        })
            .then(function (response) {

              const results = [];

                for (const [key, value] of Object.entries(response.data)) {
                    results.push(value);
                }

                setSuggestions(results)
            })
            .catch(function (error) {
                console.log(error);
            })
    }

    function searchUsers() {
        const params = {
            search_value: searchText,
            values: props.values,
            targetEntity: props.targetEntity,
            targetBundle: props.targetBundle,
            disableTop: this.props.disableTop,
            page: props.page,
            current_group: props.group
        };

        axios.get(props.url, {
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

                setSuggestions(results)
            })
            .catch(function (error) {
                console.log(error);
            })
    }

    function search() {
        if (props.searchSpecificUsers) {
            searchUsers();
        } else {
            searchTerms();
        }
    }

    useEffect(() => {
        search()
    }, [])

    function handleSearchChange(e) {
      setSearchText(e.target.value)
      search()
      if(e.target.value.length > 1) {
        setDropdownIsOpen(true)
      } else {
        setDropdownIsOpen(false)
      }
    }

    function handleDropdownClick() {
      setDropdownIsOpen(!dropdownIsOpen)
    }

    console.log(props.results);

    return (
        <div className='entity-tree__result-items'>
            <div className='entity-tree__input-wrapper form-select ecl-select'>
                {value.map((option, index) => (
                    <Tag label={option.name} {...getTagProps({ index })} />
                ))}
                <input value={searchText} onChange={handleSearchChange} placeholder='Search' />

                <div onClick={handleDropdownClick} className="ecl-select__icon" dangerouslySetInnerHTML={{__html: svg('arrow', 'ecl-icon ecl-icon--s ecl-select__icon-shape')}} />

            </div>
            {(dropdownIsOpen) > 0 && (
                <div className='entity-tree__list'>
                    {
                      searchText.length > 1 ?
                      suggestions.map((option, index) => <SelectLine onClick={() => {console.log('hello')}} search={searchText} option={option} key={index}> <b>{option.name}</b> </SelectLine>)
                      :
                      props.results.map((option, index) => <SelectLine onClick={() => {console.log('hello')}} search={searchText} option={option} key={index}> <b>{option.name}</b> </SelectLine>)
                    }
                </div>
            )}
        </div>
    )
}

const SelectLine = ({option, onClick, search, extraClasses, children}) => {

  const childs = [];
  for (const [key, value] of Object.entries(option.children)) {
    if(search.length > 1) {
      if (value.name.toLowerCase().match(search.toLowerCase())) {
        childs.push(value);
      }
    } else {
      childs.push(value);
    }

  }

  return (
    <>
      <div className={'entity-tree__list__el ' + extraClasses}>{children}</div>
      {childs.length > 0 && childs.map((el, i) => <SelectLine onClick={onClick} extraClasses={'entity-tree__list__el--child'} option={el} key={i}>{el.name}</SelectLine>)}
    </>
  )
}

export default Autocomplete
