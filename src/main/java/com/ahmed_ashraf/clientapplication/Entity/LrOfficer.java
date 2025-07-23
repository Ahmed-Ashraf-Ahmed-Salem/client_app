package com.ahmed_ashraf.clientapplication.Entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

@Entity
@Table(name = "LR_OFFICER")
@Getter
@Setter
public class LrOfficer {

    @EmbeddedId
    private LrOfficerId id;

    @Column(name = "NO_OF_APPDELETED")
    private Integer noOfAppDeleted;
}















/*
package com.ahmed_ashraf.clientapplication.Entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

@Entity
@Table(name = "LR_OFFICER")
@Getter
@Setter
public class LrOfficer {
    @Id
    @Column(name = "BRANCH_CODE")
    private String branch_code;

    @Id
    @Column(name = "CODE")
    private String code;

    @Column(name = "NO_OF_APPDELETED")
    private Integer noOfAppDeleted;
}
*/