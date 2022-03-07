import React, { useEffect, useState } from 'react'
import useAutocomplete from '@material-ui/lab/useAutocomplete';
import axios from 'axios';

const Autocomplete = (props) => {
    const [searchText, setSearchText] = useState([])
    const [suggestions, setSuggestions] = useState([])
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
                setSuggestions(response.data)
                console.log('ici', response.data);
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

    return (
        <div className='entity-tree__result-items'>
            <div className='entity-tree__input-wrapper'>
                {value.map((option, index) => (
                    <Tag label={option.name} {...getTagProps({ index })} />
                ))}
                <input placeholder='Search' {...getInputProps()} />
            </div>
            {groupedOptions.length > 0 && (
                <ul className='entity-tree__list'  {...getListboxProps()}>
                    {groupedOptions.map((option, index) => (
                        <li {...getOptionProps({ option, index })}>
                            <span>{option.name}</span>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    )
}

export default Autocomplete