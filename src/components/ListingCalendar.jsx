import React, {useState, useEffect} from "react";

import {
    Calendar,
    ConfigProvider,
    Space,
    Select,
    Modal,
    Tooltip
} from "antd";

import {
    LeftOutlined,
    RightOutlined,
    ClockCircleOutlined
} from '@ant-design/icons';

import dayjs from 'dayjs';
import enGB from 'antd/locale/en_GB';
import localeData from 'dayjs/plugin/localeData';
import weekOfYear from 'dayjs/plugin/weekOfYear';
import updateLocale from 'dayjs/plugin/updateLocale';

dayjs.extend(localeData);
dayjs.extend(weekOfYear);
dayjs.extend(updateLocale);

const startOfWeek = 1;

dayjs.updateLocale('en', {
    weekStart: startOfWeek,
});

const ListingCalendar = () => {
    const [value, setValue] = useState(dayjs());
    const [timeModalVisible, setTimeModalVisible] = useState(false);
    const [calendarData, setCalendarData] = useState({});
    const [selectedDateSlots, setSelectedDateSlots] = useState([]);
    const [selectedDate, setSelectedDate] = useState(null);

    useEffect(() => {
        async function fetchCalendarMeta() {
            try {
                const action = 'get_listing_calendar_meta';
                const post_id = rtcl.is_listing;
                const __rtcl_wpnonce = rtcl.__rtcl_wpnonce;

                const url = new URL(rtcl.ajaxurl, window.location.origin);
                url.searchParams.append('action', action);
                url.searchParams.append('post_id', post_id);
                url.searchParams.append('__rtcl_wpnonce', __rtcl_wpnonce);

                const res = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                });

                const data = await res.json();

                if (data.success && data.data) {
                    setCalendarData(data.data);
                } else {
                    console.warn('Failed to fetch calendar data:', data);
                }
            } catch (error) {
                console.error('AJAX error:', error);
            }
        }

        fetchCalendarMeta();

    }, []);

    const headerRender = ({value, onChange}) => {
        const year = value.year();
        const month = value.month();

        const months = dayjs.monthsShort();

        const monthOptions = months.map((name, i) => (
            <Select.Option key={i} value={i}>
                {name}
            </Select.Option>
        ));

        const yearOptions = [];
        for (let i = year - 10; i <= year + 10; i++) {
            yearOptions.push(
                <Select.Option key={i} value={i}>
                    {i}
                </Select.Option>
            );
        }

        const onMonthChange = (newMonth) => {
            const newValue = value.clone().month(newMonth);
            onChange(newValue);
        };

        const onYearChange = (newYear) => {
            const newValue = value.clone().year(newYear);
            onChange(newValue);
        };

        const prevMonth = () => {
            const newValue = value.clone().subtract(1, 'month');
            onChange(newValue);
        };

        const nextMonth = () => {
            const newValue = value.clone().add(1, 'month');
            onChange(newValue);
        };

        return (
            <Space
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    gap: 15
                }}
            >
                <Space>
                    <Select
                        size='small'
                        popupMatchSelectWidth={false}
                        value={month}
                        onChange={onMonthChange}
                        style={{
                            height: 40,
                            minWidth: 80
                        }}
                    >
                        {monthOptions}
                    </Select>
                    <Select
                        size='small'
                        popupMatchSelectWidth={false}
                        value={year}
                        onChange={onYearChange}
                        style={{
                            height: 40,
                            minWidth: 80
                        }}
                    >
                        {yearOptions}
                    </Select>
                </Space>
                <Space>
                    <LeftOutlined onClick={prevMonth} style={{cursor: 'pointer'}}/>
                    <div>
                        {value.format('MMMM YYYY')}
                    </div>
                    <RightOutlined onClick={nextMonth} style={{cursor: 'pointer'}}/>
                </Space>
            </Space>
        );
    };

    const handlePanelChange = (newValue, mode) => {
        setValue(newValue);
    };

    const cellRender = (date, info) => {
        if (info.type !== 'date') return info.originNode;

        const key = date.format('YYYY-MM-DD');

        const year = date.format('YYYY');
        const month = date.format('MM');
        const day = date.format('DD');

        const dayData = calendarData?.[year]?.[month]?.[day];

        const cellStatus = dayData?.status || '';
        const hasTimeSlot = dayData?.timeSlots?.length > 0;
        const timeSlots = dayData?.timeSlots || [];

        const isAvailable = cellStatus === 'available';
        const isBooked = cellStatus === 'booked';

        const backgroundColor = isAvailable
            ? '#e6f7ff'
            : isBooked
                ? '#fff1f0'
                : undefined;


        const labelStyle = {
            fontSize: 10,
            marginTop: 2,
            color: isAvailable ? '#52c41a' : isBooked ? '#ff4d4f' : undefined,
        };

        const handleClockClick = (e, date, slots) => {
            e.stopPropagation();
            setSelectedDate(date);
            setSelectedDateSlots(slots);
            setTimeModalVisible(true);
        };

        return (
            <div
                style={{
                    position: 'relative',
                    backgroundColor: backgroundColor,
                    borderRadius: 4,
                    padding: 4,
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    width: '100%',
                    height: 60,
                }}
            >
                <div>{date.date()}</div>
                {cellStatus && (
                    <div style={labelStyle}>
                        <span>{cellStatus === 'available' ? 'Available' : 'Booked'}</span>
                        {hasTimeSlot && (
                            <>
                                <Tooltip title="See Time">
                                    <div
                                        onClick={(e) => handleClockClick(e, date, timeSlots)}
                                        style={{
                                            position: 'absolute',
                                            bottom: 0,
                                            right: 0,
                                            backgroundColor: '#d558e3',
                                            width: 20,
                                            height: 20,
                                            display: 'inline-flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            cursor: 'pointer',
                                        }}
                                    >
                                        <ClockCircleOutlined style={{fontSize: 12, color: '#fff'}}/>
                                    </div>
                                </Tooltip>
                            </>
                        )}
                    </div>
                )}
            </div>
        );
    };

    return (
        <ConfigProvider locale={enGB}>
            <Calendar
                value={value}
                headerRender={headerRender}
                fullCellRender={cellRender}
                onPanelChange={handlePanelChange}
            />
            <Modal
                title={selectedDate ? dayjs(selectedDate).format('MMMM D, YYYY') : ''}
                open={timeModalVisible}
                onCancel={() => {
                    setTimeModalVisible(false);
                    setSelectedDate(null);
                    setSelectedDateSlots([]);
                }}
                footer={null}
                centered
            >
                {selectedDateSlots.map((slot, index) => {
                    const isBooked = slot.status === 'booked';
                    const isAvailable = slot.status === 'available';

                    const slotColor = isBooked ? '#ff4d4f' : isAvailable ? 'green' : 'inherit';

                    return (
                        <div key={index} className={slot.status} style={{color: slotColor, marginBottom: 4}}>
                            <span>{dayjs(slot.start, 'HH:mm').format('HH:mm')}</span> - <span>{dayjs(slot.end, 'HH:mm').format('HH:mm')}</span>
                        </div>
                    );
                })}
            </Modal>
        </ConfigProvider>
    );
};

export default ListingCalendar;