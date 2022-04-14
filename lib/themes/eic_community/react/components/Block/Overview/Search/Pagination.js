import React from 'react';
import { usePagination } from '@material-ui/lab';
import { addParamsToUrl } from '../../../Utils/Url';
import { url } from '../../../../Services/UrlHelper';

const Pagination = (props) => {
  const { items } = usePagination({
    page: Number(props.page),
    count: Number(props.total),
    siblingCount: 0,
    onChange: (event, value) => {
      event.preventDefault();
      props.changePage(value);
      addParamsToUrl('page', value);
    },
  });

  const extraClasse = {
    previous: 'ecl-pagination__item--previous',
    next: 'ecl-pagination__item--next',
    page: 'ecl-pagination__item--page',
    'start-ellipsis': 'ecl-pagination__item--page--constant',
    'end-ellipsis': 'ecl-pagination__item--page--constant',
  };

  return (
    <ul className="ecl-pagination__list">
      {items.map(({ page, type, selected, ...item }, index) => {
        let children;
        if (type === 'start-ellipsis' || type === 'end-ellipsis') {
          children = '…';
        } else if (type === 'page') {
          children = (
            <a className={`ecl-link ecl-link--standalone ecl-pagination__link`} {...item}>
              {page}
            </a>
          );
        } else if (type === 'previous') {
          children = (
            <button className="ecl-pagination__item ecl-pagination__item--previous" {...item}>
              <svg
                className="ecl-icon ecl-icon--xs ecl-icon--rotate-270 ecl-link__icon"
                focusable="false"
                aria-hidden="true"
              >
                <use
                  href={url(
                    '/themes/custom/eic_community/dist/images/sprite/custom/sprites/custom.svg#ui--corner-arrow'
                  )}
                />
              </svg>
              {type[0].toUpperCase() + type.substring(1)}
            </button>
          );
        } else {
          children = (
            <button className="ecl-pagination__item ecl-pagination__item--next" {...item}>
              {type[0].toUpperCase() + type.substring(1)}
              <svg
                className="ecl-icon ecl-icon--xs ecl-icon--rotate-90 ecl-link__icon"
                focusable="false"
                aria-hidden="true"
              >
                <use
                  href={url(
                    '/themes/custom/eic_community/dist/images/sprite/custom/sprites/custom.svg#ui--corner-arrow'
                  )}
                />
              </svg>
            </button>
          );
        }

        return (
          <li
            className={`ecl-pagination__item ${extraClasse?.[type]}  ${
              selected ? 'ecl-pagination__item--current' : ''
            } ${page === props.total || page === 1 ? 'ecl-pagination__item--page--constant' : ''}`}
            key={index}
          >
            {children}
          </li>
        );
      })}
    </ul>
  );
};

export default Pagination;
