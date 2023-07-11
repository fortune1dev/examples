// SPDX-License-Identifier: MIT
pragma solidity ^0.8.4;

import "@openzeppelin/contracts/utils/structs/EnumerableMap.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/token/ERC20/utils/SafeERC20.sol";
import "@openzeppelin/contracts/utils/math/SafeMath.sol";

contract Accounting is Ownable {
    using EnumerableMap for EnumerableMap.AddressToUintMap;
    using EnumerableSet for EnumerableSet.AddressSet;

    using SafeMath for uint256;

    EnumerableMap.AddressToUintMap private _releaseDates;
    EnumerableSet.AddressSet private _whiteList;

    constructor() {
        //initial settings for deploy
        _whiteList.add(owner());
        _releaseDates.set(owner(), block.timestamp - 100 days);
        _whiteList.add(address(this));
        _releaseDates.set(address(this), block.timestamp - 100 days);

    }

    function getAccountInfo(address m_account) public view returns (uint) {
        require(m_account == msg.sender || msg.sender == owner(), "Access denied");
        uint date = _getVestingTime(m_account);
        require(date != 0, "The account is not in the database");
        return date;
    }

    function _checkInWL(address m_account) internal view returns (bool){
        if (_whiteList.contains(m_account)) return true;
        return false;
    }

    function _addToWL(address m_account) internal onlyOwner {
        require(m_account != address(0), "Wrong address");

        _whiteList.add(m_account);
        setVestingTime(m_account, block.timestamp - 100 days);
    }

    function _removeFromWL(address m_account) internal onlyOwner {
        require(m_account != address(0), "Wrong address");

        if (_whiteList.contains(m_account)) {
            _whiteList.remove(m_account);
            _releaseDates.set(m_account, block.timestamp);
        }
    }

    function _getWL() internal view returns (address[] memory) {
        return _whiteList.values();
    }

     /**
     * @dev Set new release date.
     */
     function setVestingTime(address m_account, uint m_timestamp) internal {
        _releaseDates.set(m_account, m_timestamp);
     }

    /**
     * @dev Getter for the start timestamp.
     */
    function _getVestingTime(address m_account) private view returns (uint) {
        (bool bSuccess, uint result) = _releaseDates.tryGet(m_account);
        if (bSuccess) return result;
        return 0;
    }

    function checkAccount(address m_account, uint256 m_totalAmount, uint256 m_amount) internal virtual view returns (bool) {
        if (address(this) == address(0)) return true;
        if (m_account == address(0)) return true;
        if (_checkInWL(m_account)) return true;
        
        uint vestingTime = _getVestingTime(m_account);
        require(vestingTime != 0, "Vesting time can not be == 0");

        if (vestingTime > block.timestamp || m_amount > _vestingSchedule(m_totalAmount, vestingTime))
            return false;
        return true;
    }

    function updateAccount(address m_from, address m_to, uint256 m_amount, uint256 m_fromBalance) internal {
        if (address(this) == address(0)) return;
        if (m_from == address(0)) return;
        if (m_to == m_from) return;
        
        require(_getVestingTime(m_from) != 0, "_getVestingTime() error");

        if(_checkInWL(m_to)){
            setVestingTime(m_to, block.timestamp - 100 days);
            // update sender
            if(_checkInWL(m_from)){
                setVestingTime(m_from, block.timestamp - 100 days);
            } else {
                uint256 oldBalance = (m_fromBalance + m_amount).div(100000).mul(167);

                uint vestingTime = _getVestingTime(m_from);
                require(vestingTime != 0, "Vesting time can't be = 0");

                uint deltaTime = (m_amount.mul(24 hours).div(oldBalance));
                uint timeX = vestingTime + deltaTime;

                setVestingTime(m_from, timeX);
            }
        } else {
            if(_checkInWL(m_from)){
                uint vestingTime = _getVestingTime(m_to);
                uint timeX = block.timestamp;

                if(vestingTime != 0){
                    timeX = vestingTime + 24 hours;
                }
                setVestingTime(m_to, timeX);
            } else {
                if(_getVestingTime(m_to) == 0){
                    setVestingTime(m_to, block.timestamp);
                }
            }
        }
    }

    function _vestingSchedule(uint256 totalAllocation, uint timestamp) private view returns (uint256) {
        uint delta = block.timestamp - timestamp;
        uint ratio = delta / 24 hours;
        if (ratio >= 1) {
            return totalAllocation * ratio;
        } else {
            return totalAllocation.mul(delta).div(24 hours);
        }
    }
}