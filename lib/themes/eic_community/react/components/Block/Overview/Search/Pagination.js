import React from 'react';
import {usePagination} from "@material-ui/lab";
import {addParamsToUrl} from "../../../Utils/Url";

const Pagination = (props) => {

  const { items } = usePagination({
    page: props.page,
    count: props.total,
    onChange: (event, value) => {
      event.preventDefault()
      props.changePage(value)
      addParamsToUrl('page',value)
    }
  });


  return (
    <ul className="ecl-pagination__list">
      {items.map(({ page, type, selected, ...item }, index) => {
        let children;

        if (type === 'start-ellipsis' || type === 'end-ellipsis') {
          children = '…';
        } else if (type === 'page') {
          children = (
            <a className={`ecl-link ecl-link--standalone ecl-pagination__link`} {...item}>{page}</a>
          );
        } else if (type === 'previous') {
          children = (
            <button className="ecl-pagination__item ecl-pagination__item--previous" {...item}>
              <svg className="ecl-icon ecl-icon--xs ecl-icon--rotate-270 ecl-link__icon" focusable="false" aria-hidden="true">
                <use href="/themes/custom/eic_community/dist/images/sprite/custom/sprites/custom.svg#ui--corner-arrow"/>
              </svg>&nbsp;
              {type[0].toUpperCase() + type.substring(1)}
            </button>
          );
        } else {
          children = (
            <button className="ecl-pagination__item ecl-pagination__item--next" {...item}>
              {type[0].toUpperCase() + type.substring(1)}&nbsp;
              <svg className="ecl-icon ecl-icon--xs ecl-icon--rotate-90 ecl-link__icon" focusable="false" aria-hidden="true">
                <use href="/themes/custom/eic_community/dist/images/sprite/custom/sprites/custom.svg#ui--corner-arrow"/>
              </svg>
            </button>
          );
        }

        return <li className={`ecl-pagination__item ${type === 'previous' && 'ecl-pagination__item--previous'} ${type === 'next' && 'ecl-pagination__item--next'}  ${selected ? 'ecl-pagination__item--current' : ''}`} key={index}>{children}</li>;
      })}
    </ul>
  )
}

export default Pagination;
